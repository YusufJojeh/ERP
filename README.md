# Advanced Project & Task Management System

A comprehensive web-based project and task management system built with PHP, MySQL, and modern web technologies.

## Features

### 🔐 Authentication & User Management
- Secure user registration and login
- Password hashing with bcrypt
- Role-based access control (Admin, Project Manager, Member)
- User profile management
- Password change functionality

### 📊 Dashboard & Analytics
- Interactive dashboard with real-time statistics
- Chart.js integration for data visualization
- Project status, task priority, and completion rate charts
- Daily activity tracking
- Export functionality (PDF/Excel)
- Date range filtering

### 📋 Project Management
- Create, edit, and delete projects
- Project status tracking (Planning, Active, On Hold, Completed, Cancelled)
- Priority levels (Low, Medium, High, Critical)
- Budget tracking
- Progress monitoring
- Team member assignment
- Project timeline management

### ✅ Task Management
- Create and assign tasks to team members
- Task status workflow (Pending, In Progress, Review, Completed)
- Priority levels and due date tracking
- Task comments and collaboration
- Overdue task alerts
- Time tracking (estimated vs actual hours)

### 👥 Team Collaboration
- Project member management
- Role-based permissions
- Activity logging and notifications
- Real-time updates
- Comment system for tasks

### 📈 Reporting & Analytics
- Comprehensive project statistics
- Task completion rates
- Team performance metrics
- Activity logs and audit trails
- Exportable reports

## Technology Stack

- **Backend**: PHP 7.4+ (Native)
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **UI Framework**: Bootstrap 5
- **Charts**: Chart.js
- **Icons**: Font Awesome 6

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- XAMPP/WAMP/LAMP (for local development)

### Setup Instructions

1. **Clone or Download the Project**
   ```bash
   git clone <repository-url>
   # or download and extract the ZIP file
   ```

2. **Database Setup**
   - Create a new MySQL database
   - Import the `database.sql` file to create tables and sample data
   ```sql
   CREATE DATABASE erp_task_management;
   USE erp_task_management;
   SOURCE database.sql;
   ```

3. **Configuration**
   - Update database credentials in `config/database.php`
   ```php
   private $host = 'localhost';
   private $db_name = 'erp_task_management';
   private $username = 'root';
   private $password = 'your_password';
   ```

4. **Web Server Configuration**
   - Place the project in your web server's document root
   - Ensure mod_rewrite is enabled (for Apache)
   - Set proper file permissions

5. **Access the Application**
   - Open your browser and navigate to `http://localhost/ERP`
   - Use the default login credentials:
     - **Admin**: username: `admin`, password: `password`
     - **Project Manager**: username: `john.doe`, password: `password`
     - **Member**: username: `mike.wilson`, password: `password`

## Default User Accounts

| Username | Password | Role | Description |
|----------|----------|------|-------------|
| admin | password | Admin | System Administrator |
| john.doe | password | Project Manager | Project Manager |
| jane.smith | password | Project Manager | Project Manager |
| mike.wilson | password | Member | Team Member |
| sarah.jones | password | Member | Team Member |
| david.brown | password | Member | Team Member |
| lisa.garcia | password | Member | Team Member |

## Project Structure

```
ERP/
├── config/
│   ├── database.php          # Database configuration
│   └── config.php            # Application settings
├── models/
│   ├── User.php              # User model
│   ├── Project.php           # Project model
│   ├── Task.php              # Task model
│   ├── Comment.php           # Comment model
│   └── ActivityLog.php       # Activity log model
├── controllers/
│   ├── AuthController.php    # Authentication controller
│   ├── DashboardController.php # Dashboard controller
│   ├── ProjectController.php # Project controller
│   ├── TaskController.php    # Task controller
│   └── UserController.php    # User controller
├── views/
│   ├── includes/
│   │   ├── header.php        # Common header
│   │   └── footer.php        # Common footer
│   ├── auth/
│   │   ├── login.php         # Login page
│   │   └── register.php      # Registration page
│   ├── dashboard/
│   │   └── dashboard.php     # Main dashboard
│   ├── projects/
│   │   ├── list.php          # Project list
│   │   ├── create.php        # Create project
│   │   ├── view.php          # Project details
│   │   └── edit.php          # Edit project
│   ├── tasks/
│   │   ├── list.php          # Task list
│   │   ├── create.php        # Create task
│   │   ├── view.php          # Task details
│   │   └── edit.php          # Edit task
│   └── users/
│       ├── profile.php       # User profile
│       └── change_password.php # Change password
├── assets/
│   ├── css/
│   │   └── style.css         # Custom styles
│   ├── js/
│   │   └── main.js           # Main JavaScript
│   └── images/               # Image assets
├── uploads/                  # File uploads directory
├── index.php                 # Main entry point
├── database.sql              # Database schema
└── README.md                 # This file
```

## Key Features Explained

### Security Features
- **Password Hashing**: All passwords are hashed using bcrypt
- **SQL Injection Prevention**: Prepared statements used throughout
- **XSS Protection**: Input sanitization and output escaping
- **CSRF Protection**: Token validation for forms
- **Session Management**: Secure session handling

### Role-Based Access Control
- **Admin**: Full system access, user management
- **Project Manager**: Create projects, assign tasks, manage teams
- **Member**: View assigned tasks, update status, add comments

### Dashboard Features
- Real-time statistics and metrics
- Interactive charts and graphs
- Recent activities feed
- Quick action buttons
- Export functionality

### Project Management
- Complete project lifecycle management
- Team member assignment
- Progress tracking
- Budget monitoring
- Status management

### Task Management
- Hierarchical task organization
- Assignment and delegation
- Status workflow
- Priority management
- Due date tracking

## Customization

### Adding New Features
1. Create model in `models/` directory
2. Create controller in `controllers/` directory
3. Create views in `views/` directory
4. Update routing in `index.php`

### Styling
- Modify `assets/css/style.css` for custom styles
- Bootstrap 5 classes can be used throughout
- Responsive design included

### Database
- Add new tables in `database.sql`
- Update models to handle new data
- Run migrations as needed

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **Permission Denied**
   - Check file permissions (755 for directories, 644 for files)
   - Ensure web server has read access

3. **Session Issues**
   - Check PHP session configuration
   - Ensure session directory is writable

4. **Chart Not Displaying**
   - Check browser console for JavaScript errors
   - Verify Chart.js is loading correctly

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the [MIT License](LICENSE).

## Support

For support and questions:
- Create an issue in the repository
- Check the troubleshooting section
- Review the code documentation

## Changelog

### Version 1.0.0
- Initial release
- Complete project and task management system
- User authentication and role management
- Dashboard with analytics
- Export functionality
- Responsive design

---

**Note**: This is a demonstration project. For production use, consider additional security measures, performance optimizations, and comprehensive testing.
