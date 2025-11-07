# SmartQuiz Hub - Web-Based Quiz Creator & Auto-Grading System

🌟 **A comprehensive quiz platform built with PHP, MySQL, HTML, CSS, and JavaScript**

## Overview

SmartQuiz Hub is a modern, responsive web application that allows teachers/admins to create quizzes with automatic grading, while students/users can attempt quizzes, view their scores instantly, and track their progress. The platform offers a complete learning management experience with user-friendly interfaces for both learners and educators.

## ✨ Features

### 🔹 User Features
- **Authentication System**: Secure signup/login with password reset functionality
- **Responsive Dashboard**: Personal dashboard with quiz history and performance analytics
- **Quiz Taking Interface**: Interactive quiz interface with timer and auto-save
- **Instant Results**: Automatic grading with detailed result analysis
- **Leaderboard**: Compete with other users and track rankings
- **Profile Management**: Update personal information and view achievements
- **Dark/Light Mode**: Toggle between themes for better user experience

### 🔹 Admin Features
- **Quiz Management**: Create, edit, and manage quizzes with various question types
- **Question Builder**: Support for Multiple Choice, True/False, and Short Answer questions
- **User Management**: Monitor user activities and manage accounts
- **Analytics Dashboard**: Comprehensive reports and statistics
- **Category Management**: Organize quizzes by categories
- **Content Control**: Activate/deactivate quizzes and manage visibility

### 🔹 Technical Features
- **Auto-Grading System**: Instant scoring for objective questions
- **Timer Functionality**: Configurable time limits with auto-submission
- **Progress Tracking**: Detailed analytics and performance insights
- **Responsive Design**: Mobile-first design that works on all devices
- **Security**: Input validation, SQL injection protection, and secure sessions
- **Modern UI**: Bootstrap 5 with custom styling and smooth animations

## 🛠️ Technology Stack

| Component | Technology |
|-----------|------------|
| **Frontend** | HTML5, CSS3, JavaScript, Bootstrap 5 |
| **Backend** | PHP 7.4+ |
| **Database** | MySQL 5.7+ |
| **Icons** | Font Awesome 6 |
| **Charts** | Chart.js |
| **Server** | Apache (XAMPP/WAMP recommended) |

## 📋 Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Web Server**: Apache or Nginx
- **Browser**: Modern browser with JavaScript enabled

## 🚀 Installation

### 1. Clone or Download
```bash
git clone https://github.com/yourusername/smartquiz-hub.git
# OR download and extract the ZIP file
```

### 2. Setup Database
1. Start your MySQL server (XAMPP/WAMP)
2. Create a new database named `smartquiz_hub`
3. Import the database schema:
   ```sql
   mysql -u root -p smartquiz_hub < database/schema.sql
   ```

### 3. Configure Database Connection
Edit `config/database.php` and update the database credentials:
```php
private $host = 'localhost';
private $db_name = 'smartquiz_hub';
private $username = 'root';
private $password = ''; // Your MySQL password
```

### 4. Setup Web Server
1. Copy the project folder to your web server directory:
   - **XAMPP**: `C:\xampp\htdocs\smartquiz-hub`
   - **WAMP**: `C:\wamp64\www\smartquiz-hub`
   - **Linux**: `/var/www/html/smartquiz-hub`

2. Start your web server and navigate to:
   ```
   http://localhost/smartquiz-hub
   ```

### 5. Default Admin Account
- **Email**: `admin@smartquizhub.com`
- **Password**: `admin123`

## 📁 Project Structure

```
smartquiz-hub/
├── admin/                  # Admin panel files
│   ├── index.php          # Admin dashboard
│   ├── quizzes.php        # Quiz management
│   ├── quiz-create.php    # Create new quiz
│   └── ...
├── assets/                # Static assets
│   ├── css/
│   │   └── style.css      # Custom styles
│   └── js/
│       └── main.js        # JavaScript functions
├── config/                # Configuration files
│   ├── config.php         # Main configuration
│   └── database.php       # Database connection
├── database/              # Database files
│   └── schema.sql         # Database structure
├── uploads/               # File uploads (create this folder)
├── index.php              # Landing page
├── login.php              # User login
├── register.php           # User registration
├── dashboard.php          # User dashboard
├── quizzes.php            # Quiz listing
├── take-quiz.php          # Quiz preview
├── quiz-interface.php     # Quiz taking interface
├── quiz-result.php        # Quiz results
├── leaderboard.php        # Leaderboard
├── contact.php            # Contact form
└── README.md              # This file
```

## 🎯 Usage Guide

### For Students/Users:
1. **Register**: Create an account or login
2. **Browse Quizzes**: Explore available quizzes by category
3. **Take Quiz**: Start a quiz and answer questions within the time limit
4. **View Results**: Get instant feedback and detailed analysis
5. **Track Progress**: Monitor your performance on the dashboard
6. **Compete**: Check your ranking on the leaderboard

### For Teachers/Admins:
1. **Login**: Use admin credentials to access the admin panel
2. **Create Categories**: Organize quizzes by subjects
3. **Create Quizzes**: Set up new quizzes with title, description, and settings
4. **Add Questions**: Create various types of questions with correct answers
5. **Manage Users**: Monitor student progress and manage accounts
6. **View Reports**: Access detailed analytics and export data

## 🔧 Configuration Options

### Quiz Settings:
- **Time Limits**: 1-300 minutes
- **Question Types**: Multiple Choice, True/False, Short Answer
- **Retake Policy**: Allow/disallow retakes
- **Question Order**: Sequential or randomized
- **Grading**: Automatic for objective questions

### System Settings:
- **Email Configuration**: Setup SMTP for password reset emails
- **File Uploads**: Configure maximum file sizes for question images
- **Security**: Adjust session timeout and password requirements
- **Appearance**: Customize site name, tagline, and branding

## 🛡️ Security Features

- **Password Hashing**: Secure password storage using PHP's password_hash()
- **SQL Injection Protection**: Prepared statements for all database queries
- **Input Validation**: Server-side validation for all user inputs
- **Session Management**: Secure session handling with timeout
- **CSRF Protection**: Protection against cross-site request forgery
- **XSS Prevention**: Output escaping to prevent cross-site scripting

## 📱 Mobile Responsiveness

The application is fully responsive and provides an optimal experience across all devices:
- **Mobile**: Touch-friendly interface with bottom navigation
- **Tablet**: Optimized layout for medium screens
- **Desktop**: Full-featured interface with sidebar navigation

## 🎨 Customization

### Themes:
- Built-in dark/light mode toggle
- CSS custom properties for easy color customization
- Bootstrap 5 utility classes for rapid styling

### Branding:
- Update site name and tagline in `config/config.php`
- Replace logo and favicon in the assets folder
- Customize colors in `assets/css/style.css`

## 🐛 Troubleshooting

### Common Issues:

1. **Database Connection Error**:
   - Check database credentials in `config/database.php`
   - Ensure MySQL server is running
   - Verify database name exists

2. **Permission Denied**:
   - Set proper file permissions (755 for directories, 644 for files)
   - Ensure web server has read/write access to uploads folder

3. **Session Issues**:
   - Check PHP session configuration
   - Ensure cookies are enabled in browser
   - Verify session save path is writable

4. **Email Not Working**:
   - Configure SMTP settings in `config/config.php`
   - Check firewall settings for SMTP ports
   - Verify email credentials

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

For support and questions:
- **Email**: support@smartquizhub.com
- **Documentation**: Check the inline comments in the code
- **Issues**: Report bugs on the GitHub issues page

## 🚀 Future Enhancements

- [ ] Real-time notifications
- [ ] Advanced question types (drag-drop, matching)
- [ ] Bulk question import from CSV/Excel
- [ ] Integration with LMS platforms
- [ ] Mobile app development
- [ ] Advanced analytics with machine learning
- [ ] Multi-language support
- [ ] Video/audio question support

## 🙏 Acknowledgments

- Bootstrap team for the excellent CSS framework
- Font Awesome for the comprehensive icon library
- Chart.js for beautiful data visualizations
- PHP community for continuous improvements
- All contributors and testers

---

**Made with ❤️ for education and learning**

*SmartQuiz Hub - Practice. Learn. Improve.*
