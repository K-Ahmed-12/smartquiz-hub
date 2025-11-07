<?php
/**
 * Add Sample Questions and Quizzes to SmartQuiz Hub
 * Run this after setting up the database to populate with test data
 */

require_once 'config/config.php';

try {
    $pdo = getDB();
    
    echo "<h2>Adding Sample Data to SmartQuiz Hub</h2>";
    echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px;'>";
    
    // Get admin user ID
    $adminStmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $admin = $adminStmt->fetch();
    
    if (!$admin) {
        throw new Exception("No admin user found. Please run setup.php first.");
    }
    
    $adminId = $admin['id'];
    echo "<p>✓ Found admin user (ID: $adminId)</p>";
    
    // Get categories
    $categoriesStmt = $pdo->query("SELECT id, name FROM categories");
    $categories = $categoriesStmt->fetchAll();
    
    if (empty($categories)) {
        throw new Exception("No categories found. Please run setup.php first.");
    }
    
    echo "<p>✓ Found " . count($categories) . " categories</p>";
    
    // Sample quizzes data
    $sampleQuizzes = [
        [
            'title' => 'General Knowledge Quiz',
            'description' => 'Test your general knowledge with this fun quiz covering various topics.',
            'category' => 'General Knowledge',
            'difficulty' => 'medium',
            'time_limit' => 15,
            'questions' => [
                [
                    'text' => 'What is the capital of France?',
                    'type' => 'multiple_choice',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'London', 'correct' => false],
                        ['text' => 'Berlin', 'correct' => false],
                        ['text' => 'Paris', 'correct' => true],
                        ['text' => 'Madrid', 'correct' => false]
                    ]
                ],
                [
                    'text' => 'Which planet is known as the Red Planet?',
                    'type' => 'multiple_choice',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'Venus', 'correct' => false],
                        ['text' => 'Mars', 'correct' => true],
                        ['text' => 'Jupiter', 'correct' => false],
                        ['text' => 'Saturn', 'correct' => false]
                    ]
                ],
                [
                    'text' => 'The Great Wall of China is visible from space.',
                    'type' => 'true_false',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'True', 'correct' => false],
                        ['text' => 'False', 'correct' => true]
                    ]
                ],
                [
                    'text' => 'What is the largest ocean on Earth?',
                    'type' => 'multiple_choice',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'Atlantic Ocean', 'correct' => false],
                        ['text' => 'Indian Ocean', 'correct' => false],
                        ['text' => 'Pacific Ocean', 'correct' => true],
                        ['text' => 'Arctic Ocean', 'correct' => false]
                    ]
                ],
                [
                    'text' => 'Who painted the Mona Lisa?',
                    'type' => 'multiple_choice',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'Vincent van Gogh', 'correct' => false],
                        ['text' => 'Leonardo da Vinci', 'correct' => true],
                        ['text' => 'Pablo Picasso', 'correct' => false],
                        ['text' => 'Michelangelo', 'correct' => false]
                    ]
                ]
            ]
        ],
        [
            'title' => 'Programming Basics',
            'description' => 'Test your knowledge of basic programming concepts and languages.',
            'category' => 'Programming',
            'difficulty' => 'easy',
            'time_limit' => 20,
            'questions' => [
                [
                    'text' => 'What does HTML stand for?',
                    'type' => 'multiple_choice',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'Hypertext Markup Language', 'correct' => true],
                        ['text' => 'High Tech Modern Language', 'correct' => false],
                        ['text' => 'Home Tool Markup Language', 'correct' => false],
                        ['text' => 'Hyperlink and Text Markup Language', 'correct' => false]
                    ]
                ],
                [
                    'text' => 'Which of the following is a programming language?',
                    'type' => 'multiple_choice',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'HTML', 'correct' => false],
                        ['text' => 'CSS', 'correct' => false],
                        ['text' => 'JavaScript', 'correct' => true],
                        ['text' => 'XML', 'correct' => false]
                    ]
                ],
                [
                    'text' => 'Python is a compiled language.',
                    'type' => 'true_false',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'True', 'correct' => false],
                        ['text' => 'False', 'correct' => true]
                    ]
                ],
                [
                    'text' => 'What is the correct way to create a comment in JavaScript?',
                    'type' => 'multiple_choice',
                    'marks' => 1,
                    'options' => [
                        ['text' => '<!-- This is a comment -->', 'correct' => false],
                        ['text' => '// This is a comment', 'correct' => true],
                        ['text' => '# This is a comment', 'correct' => false],
                        ['text' => '/* This is a comment */', 'correct' => false]
                    ]
                ]
            ]
        ],
        [
            'title' => 'Mathematics Quiz',
            'description' => 'Challenge yourself with basic mathematical problems and concepts.',
            'category' => 'Mathematics',
            'difficulty' => 'medium',
            'time_limit' => 25,
            'questions' => [
                [
                    'text' => 'What is 15 + 27?',
                    'type' => 'multiple_choice',
                    'marks' => 1,
                    'options' => [
                        ['text' => '42', 'correct' => true],
                        ['text' => '41', 'correct' => false],
                        ['text' => '43', 'correct' => false],
                        ['text' => '40', 'correct' => false]
                    ]
                ],
                [
                    'text' => 'What is the square root of 64?',
                    'type' => 'multiple_choice',
                    'marks' => 1,
                    'options' => [
                        ['text' => '6', 'correct' => false],
                        ['text' => '7', 'correct' => false],
                        ['text' => '8', 'correct' => true],
                        ['text' => '9', 'correct' => false]
                    ]
                ],
                [
                    'text' => 'Pi (π) is approximately equal to 3.14159.',
                    'type' => 'true_false',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false]
                    ]
                ],
                [
                    'text' => 'What is 12 × 8?',
                    'type' => 'multiple_choice',
                    'marks' => 1,
                    'options' => [
                        ['text' => '94', 'correct' => false],
                        ['text' => '95', 'correct' => false],
                        ['text' => '96', 'correct' => true],
                        ['text' => '97', 'correct' => false]
                    ]
                ]
            ]
        ],
        [
            'title' => 'Science Fundamentals',
            'description' => 'Basic science questions covering physics, chemistry, and biology.',
            'category' => 'Science',
            'difficulty' => 'medium',
            'time_limit' => 20,
            'questions' => [
                [
                    'text' => 'What is the chemical symbol for water?',
                    'type' => 'multiple_choice',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'H2O', 'correct' => true],
                        ['text' => 'CO2', 'correct' => false],
                        ['text' => 'O2', 'correct' => false],
                        ['text' => 'H2', 'correct' => false]
                    ]
                ],
                [
                    'text' => 'How many bones are in the adult human body?',
                    'type' => 'multiple_choice',
                    'marks' => 1,
                    'options' => [
                        ['text' => '204', 'correct' => false],
                        ['text' => '206', 'correct' => true],
                        ['text' => '208', 'correct' => false],
                        ['text' => '210', 'correct' => false]
                    ]
                ],
                [
                    'text' => 'The speed of light is faster than the speed of sound.',
                    'type' => 'true_false',
                    'marks' => 1,
                    'options' => [
                        ['text' => 'True', 'correct' => true],
                        ['text' => 'False', 'correct' => false]
                    ]
                ]
            ]
        ]
    ];
    
    echo "<p><strong>Creating sample quizzes and questions...</strong></p>";
    
    $quizCount = 0;
    $questionCount = 0;
    
    foreach ($sampleQuizzes as $quizData) {
        // Find category ID
        $categoryId = null;
        foreach ($categories as $category) {
            if ($category['name'] === $quizData['category']) {
                $categoryId = $category['id'];
                break;
            }
        }
        
        if (!$categoryId) {
            echo "<p style='color: orange;'>⚠ Category '{$quizData['category']}' not found, skipping quiz</p>";
            continue;
        }
        
        // Check if quiz already exists
        $existingQuiz = $pdo->prepare("SELECT id FROM quizzes WHERE title = ?");
        $existingQuiz->execute([$quizData['title']]);
        
        if ($existingQuiz->fetch()) {
            echo "<p>✓ Quiz '<strong>{$quizData['title']}</strong>' already exists</p>";
            continue;
        }
        
        // Calculate total marks
        $totalMarks = array_sum(array_column($quizData['questions'], 'marks'));
        
        // Insert quiz
        $quizStmt = $pdo->prepare("
            INSERT INTO quizzes (title, description, category_id, difficulty, time_limit, total_marks, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $quizStmt->execute([
            $quizData['title'],
            $quizData['description'],
            $categoryId,
            $quizData['difficulty'],
            $quizData['time_limit'],
            $totalMarks,
            $adminId
        ]);
        
        $quizId = $pdo->lastInsertId();
        $quizCount++;
        
        echo "<p>✓ Created quiz: <strong>{$quizData['title']}</strong> (ID: $quizId)</p>";
        
        // Insert questions
        $questionOrder = 1;
        foreach ($quizData['questions'] as $questionData) {
            $questionStmt = $pdo->prepare("
                INSERT INTO questions (quiz_id, question_text, question_type, marks, order_number) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $questionStmt->execute([
                $quizId,
                $questionData['text'],
                $questionData['type'],
                $questionData['marks'],
                $questionOrder
            ]);
            
            $questionId = $pdo->lastInsertId();
            $questionCount++;
            
            // Insert options
            $optionOrder = 1;
            foreach ($questionData['options'] as $optionData) {
                $optionStmt = $pdo->prepare("
                    INSERT INTO question_options (question_id, option_text, is_correct, option_order) 
                    VALUES (?, ?, ?, ?)
                ");
                
                $optionStmt->execute([
                    $questionId,
                    $optionData['text'],
                    $optionData['correct'] ? 1 : 0,
                    $optionOrder
                ]);
                
                $optionOrder++;
            }
            
            $questionOrder++;
        }
    }
    
    echo "<hr>";
    echo "<h3>✅ Sample data added successfully!</h3>";
    echo "<p><strong>Summary:</strong></p>";
    echo "<ul>";
    echo "<li>Quizzes created: <strong>$quizCount</strong></li>";
    echo "<li>Questions added: <strong>$questionCount</strong></li>";
    echo "</ul>";
    
    echo "<p><strong>You can now:</strong></p>";
    echo "<ol>";
    echo "<li>Visit the <a href='quizzes.php'>Quizzes page</a> to see all available quizzes</li>";
    echo "<li>Take a quiz to test the functionality</li>";
    echo "<li>Login as admin to manage quizzes</li>";
    echo "</ol>";
    
    echo "<p style='margin-top: 30px;'>";
    echo "<a href='quizzes.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>View Quizzes</a>";
    echo "<a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Homepage</a>";
    echo "</p>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px;'>";
    echo "<h2 style='color: red;'>❌ Failed to Add Sample Data</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Please ensure:</strong></p>";
    echo "<ul>";
    echo "<li>Database is set up properly (run setup.php first)</li>";
    echo "<li>XAMPP MySQL service is running</li>";
    echo "<li>Database connection is working</li>";
    echo "</ul>";
    echo "</div>";
}
?>
