# Project Topics Validation System

A comprehensive web-based system for managing and validating student project topics across multiple departments in an academic institution.

## 📋 Features

### For Faculty Project Coordinator (FPC)
- **Dashboard** with real-time statistics
- **Manage DPCs** - Create, update, delete Departmental Project Coordinators
- **Upload Past Projects** - Build a database of historical projects
- **Manage Topics** - Oversee all project topics across departments
- **Session Management** - Handle academic year transitions

### For Departmental Project Coordinator (DPC)
- Manage students within their department
- Validate and approve project topics
- Monitor department-specific submissions

### For Students
- Submit project topics
- Track validation status
- Receive feedback on submissions

### For Supervisors
- Review assigned projects
- Provide guidance and oversight

## 🚀 Technologies Used

- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Icons**: Font Awesome 6
- **Server**: Apache (XAMPP)

## 📦 Installation

### Prerequisites
- XAMPP (or any PHP 7.4+ and MySQL environment)
- Git
- Web browser

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone https://github.com/YOUR_USERNAME/projectVal.git
   cd projectVal
   ```

2. **Set up the database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `my_project_topics2`
   - Import the database schema (if SQL file is provided)

3. **Configure database connection**
   - Navigate to `includes/` folder
   - Copy `db.sample.php` to `db.php`
   - Edit `db.php` with your database credentials:
     ```php
     $host = 'localhost';
     $dbname = 'my_project_topics2';
     $username = 'root';
     $password = '';
     ```

4. **Move to XAMPP directory**
   - Copy the project folder to `C:\xampp\htdocs\`
   - Or create a symbolic link

5. **Start XAMPP**
   - Start Apache and MySQL services

6. **Access the application**
   - Open browser and navigate to: `http://localhost/projectVal/`

## 🗄️ Database Structure

### Main Tables
- **users** - Stores all user accounts (FPC, DPC, Students, Supervisors)
- **departments** - Academic departments
- **topics** - Project topic submissions
- **sessions** - Academic year sessions

## 👥 User Roles

| Role | Username Example | Default Password |
|------|-----------------|------------------|
| FPC  | fpc             | (set during setup) |
| DPC  | dpc_cs          | TempPassword123 |
| Student | student_001  | (set during registration) |
| Supervisor | sup_001   | (set during setup) |

## 🎨 Features Highlights

### Modern UI/UX
- Responsive design for all devices
- Premium gradient color schemes
- Smooth animations and transitions
- Toast notifications for user feedback
- Modal dialogs for forms
- AJAX-powered operations (no page reloads)

### Security Features
- Password hashing with bcrypt
- SQL injection protection with prepared statements
- Session management with timeout
- Role-based access control
- Input validation (client and server-side)

### Advanced Functionality
- Real-time search and filtering
- Pagination for large datasets
- Bulk operations support
- Export functionality
- Activity status management
- Password reset capabilities

## 📁 Project Structure

```
projectVal/
├── assets/
│   ├── css/
│   │   └── styles.css
│   └── js/
│       └── scripts.js
├── includes/
│   ├── auth.php
│   ├── db.sample.php
│   ├── header.php
│   └── footer.php
├── faculty_project_coordinator/
│   ├── fpc_dashboard.php
│   ├── fpc_manage_dpc.php
│   ├── fpc_manage_topics.php
│   └── fpc_upload_past_projects.php
├── department_project_coordinator/
│   └── (DPC files)
├── student/
│   └── (Student files)
├── supervisor/
│   └── (Supervisor files)
├── index.php
├── logout.php
├── .gitignore
└── README.md
```

## 🔧 Configuration

### Session Timeout
Default: 30 minutes of inactivity
Location: `includes/auth.php`

### Temporary Password
Default: `TempPassword123`
Location: `faculty_project_coordinator/fpc_manage_dpc.php`

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 👨‍💻 Author

**Your Name**
- GitHub: [@YOUR_USERNAME](https://github.com/YOUR_USERNAME)

## 🙏 Acknowledgments

- Font Awesome for icons
- XAMPP for local development environment
- All contributors and testers

## 📞 Support

For support, email your-email@example.com or open an issue in the GitHub repository.

---

**Note**: Remember to never commit sensitive information like database credentials. Always use environment variables or configuration files that are excluded from version control.
