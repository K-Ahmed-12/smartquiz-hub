<?php
/**
 * Sample Quiz Setup Script for SmartQuiz Hub
 * Creates sample quizzes with 20 multiple choice questions for each category
 */

require_once 'config/config.php';

// Require admin access
requireAdmin();

echo "<h2>SmartQuiz Hub - Sample Quiz Setup</h2>";
echo "<p>This script will create sample quizzes with 20 multiple choice questions for each category.</p>";
echo "<p><strong>Quiz Settings:</strong> 10 minutes, 20 marks (1 mark per question)</p>";

try {
    $db = getDB();
    
    // Get all categories
    $stmt = $db->prepare("SELECT * FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
    if (empty($categories)) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "<h4>❌ No Categories Found!</h4>";
        echo "<p>Please create categories first or run the database setup script.</p>";
        echo "</div>";
        exit;
    }
    
    echo "<h3>Found " . count($categories) . " categories</h3>";
    
    // Sample questions for different categories
    $sample_questions = [
        'General Knowledge' => [
            ['What is the capital of France?', ['Paris', 'London', 'Berlin', 'Madrid'], 0],
            ['Which planet is known as the Red Planet?', ['Venus', 'Mars', 'Jupiter', 'Saturn'], 1],
            ['What is the largest ocean on Earth?', ['Atlantic', 'Indian', 'Arctic', 'Pacific'], 3],
            ['Who painted the Mona Lisa?', ['Van Gogh', 'Picasso', 'Leonardo da Vinci', 'Michelangelo'], 2],
            ['What is the smallest country in the world?', ['Monaco', 'Vatican City', 'San Marino', 'Liechtenstein'], 1],
            ['Which element has the chemical symbol "O"?', ['Gold', 'Oxygen', 'Silver', 'Iron'], 1],
            ['What year did World War II end?', ['1944', '1945', '1946', '1947'], 1],
            ['Which is the longest river in the world?', ['Amazon', 'Nile', 'Mississippi', 'Yangtze'], 1],
            ['What is the hardest natural substance on Earth?', ['Gold', 'Iron', 'Diamond', 'Platinum'], 2],
            ['Which country is home to the kangaroo?', ['New Zealand', 'Australia', 'South Africa', 'Argentina'], 1],
            ['What is the largest mammal in the world?', ['Elephant', 'Blue Whale', 'Giraffe', 'Hippopotamus'], 1],
            ['Which gas makes up most of Earth\'s atmosphere?', ['Oxygen', 'Carbon Dioxide', 'Nitrogen', 'Hydrogen'], 2],
            ['What is the currency of Japan?', ['Yuan', 'Won', 'Yen', 'Rupee'], 2],
            ['Which mountain range contains Mount Everest?', ['Andes', 'Rockies', 'Alps', 'Himalayas'], 3],
            ['What is the speed of light?', ['300,000 km/s', '150,000 km/s', '450,000 km/s', '600,000 km/s'], 0],
            ['Which organ in the human body produces insulin?', ['Liver', 'Pancreas', 'Kidney', 'Heart'], 1],
            ['What is the largest desert in the world?', ['Sahara', 'Gobi', 'Antarctica', 'Arabian'], 2],
            ['Which Shakespeare play features the character Hamlet?', ['Macbeth', 'Othello', 'Hamlet', 'Romeo and Juliet'], 2],
            ['What is the most abundant gas in the universe?', ['Oxygen', 'Hydrogen', 'Helium', 'Nitrogen'], 1],
            ['Which city hosted the 2016 Summer Olympics?', ['London', 'Tokyo', 'Rio de Janeiro', 'Beijing'], 2]
        ],
        
        'Science' => [
            ['What is the chemical formula for water?', ['H2O', 'CO2', 'NaCl', 'CH4'], 0],
            ['Which scientist developed the theory of relativity?', ['Newton', 'Einstein', 'Darwin', 'Galileo'], 1],
            ['What is the atomic number of carbon?', ['4', '6', '8', '12'], 1],
            ['Which force keeps planets in orbit around the sun?', ['Magnetic', 'Electric', 'Gravitational', 'Nuclear'], 2],
            ['What is the powerhouse of the cell?', ['Nucleus', 'Mitochondria', 'Ribosome', 'Cytoplasm'], 1],
            ['Which blood type is known as the universal donor?', ['A', 'B', 'AB', 'O'], 3],
            ['What is the study of earthquakes called?', ['Geology', 'Seismology', 'Meteorology', 'Astronomy'], 1],
            ['Which gas is produced during photosynthesis?', ['Carbon Dioxide', 'Oxygen', 'Nitrogen', 'Hydrogen'], 1],
            ['What is the smallest unit of matter?', ['Molecule', 'Atom', 'Electron', 'Proton'], 1],
            ['Which planet has the most moons?', ['Jupiter', 'Saturn', 'Uranus', 'Neptune'], 1],
            ['What is the pH of pure water?', ['6', '7', '8', '9'], 1],
            ['Which scientist is known for the laws of motion?', ['Einstein', 'Newton', 'Galileo', 'Kepler'], 1],
            ['What type of bond holds water molecules together?', ['Ionic', 'Covalent', 'Hydrogen', 'Metallic'], 2],
            ['Which organ is responsible for filtering blood?', ['Liver', 'Heart', 'Kidney', 'Lung'], 2],
            ['What is the most common element in the Earth\'s crust?', ['Silicon', 'Oxygen', 'Aluminum', 'Iron'], 1],
            ['Which process converts sugar into energy in cells?', ['Photosynthesis', 'Respiration', 'Digestion', 'Circulation'], 1],
            ['What is the unit of electric current?', ['Volt', 'Ampere', 'Ohm', 'Watt'], 1],
            ['Which layer of the atmosphere contains the ozone layer?', ['Troposphere', 'Stratosphere', 'Mesosphere', 'Thermosphere'], 1],
            ['What is the chemical symbol for gold?', ['Go', 'Gd', 'Au', 'Ag'], 2],
            ['Which type of radiation has the longest wavelength?', ['Gamma', 'X-ray', 'Ultraviolet', 'Radio'], 3]
        ],
        
        'Technology' => [
            ['What does CPU stand for?', ['Central Processing Unit', 'Computer Personal Unit', 'Central Program Unit', 'Computer Processing Unit'], 0],
            ['Which programming language is known as the "language of the web"?', ['Python', 'Java', 'JavaScript', 'C++'], 2],
            ['What does HTML stand for?', ['Hypertext Markup Language', 'High Tech Modern Language', 'Home Tool Markup Language', 'Hyperlink Text Markup Language'], 0],
            ['Which company developed the Android operating system?', ['Apple', 'Microsoft', 'Google', 'Samsung'], 2],
            ['What is the binary representation of the decimal number 8?', ['1000', '1001', '1010', '1100'], 0],
            ['Which protocol is used for secure web browsing?', ['HTTP', 'HTTPS', 'FTP', 'SMTP'], 1],
            ['What does RAM stand for?', ['Random Access Memory', 'Read Access Memory', 'Rapid Access Memory', 'Remote Access Memory'], 0],
            ['Which company created the iPhone?', ['Samsung', 'Google', 'Apple', 'Microsoft'], 2],
            ['What is the most popular database management system?', ['Oracle', 'MySQL', 'PostgreSQL', 'MongoDB'], 1],
            ['Which programming language was developed by Guido van Rossum?', ['Java', 'Python', 'C++', 'Ruby'], 1],
            ['What does URL stand for?', ['Universal Resource Locator', 'Uniform Resource Locator', 'Universal Reference Link', 'Uniform Reference Locator'], 1],
            ['Which technology is used for wireless communication over short distances?', ['WiFi', 'Bluetooth', '4G', '5G'], 1],
            ['What is the maximum length of a tweet on Twitter?', ['140 characters', '280 characters', '500 characters', '1000 characters'], 1],
            ['Which company owns YouTube?', ['Facebook', 'Google', 'Microsoft', 'Amazon'], 1],
            ['What does AI stand for?', ['Automated Intelligence', 'Artificial Intelligence', 'Advanced Intelligence', 'Algorithmic Intelligence'], 1],
            ['Which programming paradigm does Java primarily follow?', ['Functional', 'Procedural', 'Object-Oriented', 'Logic'], 2],
            ['What is the standard port number for HTTP?', ['21', '25', '80', '443'], 2],
            ['Which company developed the Windows operating system?', ['Apple', 'Google', 'Microsoft', 'IBM'], 2],
            ['What does CSS stand for?', ['Computer Style Sheets', 'Creative Style Sheets', 'Cascading Style Sheets', 'Colorful Style Sheets'], 2],
            ['Which social media platform is known for professional networking?', ['Facebook', 'Twitter', 'Instagram', 'LinkedIn'], 3]
        ],
        
        'History' => [
            ['In which year did World War I begin?', ['1912', '1914', '1916', '1918'], 1],
            ['Who was the first President of the United States?', ['Thomas Jefferson', 'John Adams', 'George Washington', 'Benjamin Franklin'], 2],
            ['Which ancient wonder of the world was located in Alexandria?', ['Colossus of Rhodes', 'Lighthouse of Alexandria', 'Hanging Gardens', 'Statue of Zeus'], 1],
            ['The Renaissance period began in which country?', ['France', 'Germany', 'Italy', 'England'], 2],
            ['Who wrote "The Communist Manifesto"?', ['Lenin', 'Marx and Engels', 'Stalin', 'Trotsky'], 1],
            ['Which empire was ruled by Julius Caesar?', ['Greek', 'Roman', 'Persian', 'Egyptian'], 1],
            ['The Berlin Wall fell in which year?', ['1987', '1989', '1991', '1993'], 1],
            ['Who was known as the "Iron Lady"?', ['Queen Elizabeth', 'Margaret Thatcher', 'Indira Gandhi', 'Golda Meir'], 1],
            ['Which war was fought between the North and South in America?', ['Revolutionary War', 'Civil War', 'War of 1812', 'Spanish-American War'], 1],
            ['Who discovered America in 1492?', ['Vasco da Gama', 'Christopher Columbus', 'Ferdinand Magellan', 'Amerigo Vespucci'], 1],
            ['The French Revolution began in which year?', ['1787', '1789', '1791', '1793'], 1],
            ['Which Egyptian queen was known for her relationships with Julius Caesar and Mark Antony?', ['Nefertiti', 'Hatshepsut', 'Cleopatra', 'Ankhesenamun'], 2],
            ['The Magna Carta was signed in which year?', ['1205', '1215', '1225', '1235'], 1],
            ['Who was the leader of Nazi Germany?', ['Heinrich Himmler', 'Adolf Hitler', 'Joseph Goebbels', 'Hermann Göring'], 1],
            ['Which ancient civilization built Machu Picchu?', ['Aztec', 'Maya', 'Inca', 'Olmec'], 2],
            ['The Industrial Revolution began in which country?', ['France', 'Germany', 'England', 'United States'], 2],
            ['Who painted the ceiling of the Sistine Chapel?', ['Leonardo da Vinci', 'Raphael', 'Michelangelo', 'Donatello'], 2],
            ['Which war ended with the Treaty of Versailles?', ['World War I', 'World War II', 'Franco-Prussian War', 'Napoleonic Wars'], 0],
            ['The Boston Tea Party occurred in which year?', ['1771', '1773', '1775', '1777'], 1],
            ['Who was the first man to walk on the moon?', ['Buzz Aldrin', 'Neil Armstrong', 'John Glenn', 'Alan Shepard'], 1]
        ],
        
        'Mathematics' => [
            ['What is the value of π (pi) to two decimal places?', ['3.14', '3.15', '3.16', '3.17'], 0],
            ['What is 15% of 200?', ['25', '30', '35', '40'], 1],
            ['What is the square root of 144?', ['11', '12', '13', '14'], 1],
            ['In a right triangle, what is the longest side called?', ['Adjacent', 'Opposite', 'Hypotenuse', 'Base'], 2],
            ['What is 7 × 8?', ['54', '56', '58', '60'], 1],
            ['What is the sum of angles in a triangle?', ['90°', '180°', '270°', '360°'], 1],
            ['What is 25²?', ['525', '625', '725', '825'], 1],
            ['What is the next prime number after 7?', ['9', '10', '11', '12'], 2],
            ['What is 1/4 as a decimal?', ['0.2', '0.25', '0.3', '0.4'], 1],
            ['What is the area of a circle with radius 5? (Use π = 3.14)', ['78.5', '79.5', '80.5', '81.5'], 0],
            ['What is 100 ÷ 4?', ['20', '25', '30', '35'], 1],
            ['What is the value of 2³?', ['6', '8', '9', '12'], 1],
            ['What is 3/5 as a percentage?', ['50%', '60%', '65%', '70%'], 1],
            ['What is the perimeter of a square with side length 6?', ['18', '20', '24', '36'], 2],
            ['What is 45 + 37?', ['80', '82', '84', '86'], 1],
            ['What is the median of 3, 7, 9, 12, 15?', ['7', '9', '10', '12'], 1],
            ['What is 12 × 12?', ['144', '154', '164', '174'], 0],
            ['What is 0.75 as a fraction in lowest terms?', ['3/4', '6/8', '9/12', '15/20'], 0],
            ['What is the volume of a cube with side length 3?', ['9', '18', '27', '36'], 2],
            ['What is 999 + 1?', ['1000', '1001', '1010', '1100'], 0]
        ]
    ];
    
    $created_quizzes = 0;
    $created_questions = 0;
    
    foreach ($categories as $category) {
        $category_name = $category['name'];
        
        echo "<h4>Processing category: {$category_name}</h4>";
        
        // Check if quiz already exists for this category
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM quizzes WHERE category_id = ? AND title LIKE ?");
        $stmt->execute([$category['id'], "%{$category_name} Quiz%"]);
        $existing = $stmt->fetch()['count'];
        
        if ($existing > 0) {
            echo "<p>⚠️ Quiz already exists for {$category_name}, skipping...</p>";
            continue;
        }
        
        // Create quiz for this category
        $quiz_title = "{$category_name} Quiz";
        $quiz_description = "Test your knowledge in {$category_name} with this comprehensive 20-question quiz.";
        
        $stmt = $db->prepare("
            INSERT INTO quizzes (title, description, category_id, difficulty, time_limit, total_marks, is_active, allow_retake, randomize_questions, created_by)
            VALUES (?, ?, ?, 'medium', 10, 20, 1, 1, 1, ?)
        ");
        $stmt->execute([$quiz_title, $quiz_description, $category['id'], $_SESSION['user_id']]);
        $quiz_id = $db->lastInsertId();
        
        echo "<p>✅ Created quiz: {$quiz_title}</p>";
        $created_quizzes++;
        
        // Get questions for this category
        $questions = isset($sample_questions[$category_name]) ? $sample_questions[$category_name] : $sample_questions['General Knowledge'];
        
        // Create 20 questions
        foreach ($questions as $index => $question_data) {
            $question_text = $question_data[0];
            $options = $question_data[1];
            $correct_index = $question_data[2];
            
            // Insert question
            $stmt = $db->prepare("
                INSERT INTO questions (quiz_id, question_text, question_type, marks, order_number)
                VALUES (?, ?, 'multiple_choice', 1, ?)
            ");
            $stmt->execute([$quiz_id, $question_text, $index + 1]);
            $question_id = $db->lastInsertId();
            
            // Insert options
            foreach ($options as $option_index => $option_text) {
                $is_correct = ($option_index == $correct_index) ? 1 : 0;
                
                $stmt = $db->prepare("
                    INSERT INTO question_options (question_id, option_text, is_correct, option_order)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$question_id, $option_text, $is_correct, $option_index + 1]);
            }
            
            $created_questions++;
        }
        
        echo "<p>✅ Added 20 questions to {$quiz_title}</p>";
    }
    
    echo "<h3>✅ Setup Complete!</h3>";
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>🎉 Sample Quizzes Created Successfully!</h4>";
    echo "<p><strong>Summary:</strong></p>";
    echo "<ul>";
    echo "<li>Created {$created_quizzes} quizzes</li>";
    echo "<li>Added {$created_questions} questions total</li>";
    echo "<li>Each quiz: 10 minutes, 20 marks</li>";
    echo "<li>All questions are multiple choice</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>📋 Quiz Details:</h4>";
    echo "<ul>";
    echo "<li><strong>Time Limit:</strong> 10 minutes per quiz</li>";
    echo "<li><strong>Total Marks:</strong> 20 marks (1 mark per question)</li>";
    echo "<li><strong>Question Type:</strong> Multiple Choice</li>";
    echo "<li><strong>Retakes:</strong> Allowed</li>";
    echo "<li><strong>Question Order:</strong> Randomized</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<p style='margin-top: 30px;'>";
    echo "<a href='index.php' style='background: #007bff; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Admin Dashboard</a>";
    echo "<a href='quizzes.php' style='background: #28a745; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>View Quizzes</a>";
    echo "<a href='../quizzes.php' style='background: #17a2b8; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px;'>Take Quizzes</a>";
    echo "</p>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>❌ Database Error!</h4>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
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
h4 { color: #495057; margin-bottom: 10px; }
p { margin: 10px 0; }
ul { margin: 10px 0 20px 20px; }
a { 
    display: inline-block;
    transition: all 0.3s ease;
    text-decoration: none;
}
a:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}
</style>
