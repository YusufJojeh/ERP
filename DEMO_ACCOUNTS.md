# Demo Accounts - ERP Task Management System

## Demo User Accounts

All demo accounts use the same password: **`password`**

### Admin Account
- **Username:** `admin`
- **Email:** `admin@erp.com`
- **Password:** `password`
- **Role:** Admin
- **Name:** System Administrator
- **Permissions:** Full system access, user management, all projects and tasks

---

### Project Manager Accounts

#### Project Manager 1
- **Username:** `john.doe`
- **Email:** `john.doe@erp.com`
- **Password:** `password`
- **Role:** Project Manager
- **Name:** John Doe
- **Permissions:** Create/edit/delete projects and tasks, manage team members, view all data

#### Project Manager 2
- **Username:** `jane.smith`
- **Email:** `jane.smith@erp.com`
- **Password:** `password`
- **Role:** Project Manager
- **Name:** Jane Smith
- **Permissions:** Create/edit/delete projects and tasks, manage team members, view all data

---

### Member Accounts

#### Member 1
- **Username:** `mike.wilson`
- **Email:** `mike.wilson@erp.com`
- **Password:** `password`
- **Role:** Member
- **Name:** Mike Wilson
- **Permissions:** View assigned projects/tasks only, create tasks, add comments and attachments

#### Member 2
- **Username:** `sarah.jones`
- **Email:** `sarah.jones@erp.com`
- **Password:** `password`
- **Role:** Member
- **Name:** Sarah Jones
- **Permissions:** View assigned projects/tasks only, create tasks, add comments and attachments

#### Member 3
- **Username:** `david.brown`
- **Email:** `david.brown@erp.com`
- **Password:** `password`
- **Role:** Member
- **Name:** David Brown
- **Permissions:** View assigned projects/tasks only, create tasks, add comments and attachments

#### Member 4
- **Username:** `lisa.garcia`
- **Email:** `lisa.garcia@erp.com`
- **Password:** `password`
- **Role:** Member
- **Name:** Lisa Garcia
- **Permissions:** View assigned projects/tasks only, create tasks, add comments and attachments

---

## Quick Login Guide

### For Admin Demo:
1. Go to login page
2. Username: `admin`
3. Password: `password`
4. You will have full access to all features

### For Project Manager Demo:
1. Go to login page
2. Username: `john.doe` or `jane.smith`
3. Password: `password`
4. You can manage projects and tasks, but cannot delete users

### For Member Demo:
1. Go to login page
2. Username: `mike.wilson`, `sarah.jones`, `david.brown`, or `lisa.garcia`
3. Password: `password`
4. You will see only assigned projects and tasks

---

## Notes

- All passwords are hashed using bcrypt in the database
- The password hash in database: `$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi`
- This corresponds to the plain text password: `password`
- All accounts are active by default
- Sample data (projects, tasks, comments) is included in the database

---

## Demo Scenarios

### Scenario 1: Admin Full Access
- Login as `admin`
- Show user management
- Show all projects and tasks
- Show activity logs
- Show system statistics

### Scenario 2: Project Manager Workflow
- Login as `john.doe`
- Create a new project
- Add team members
- Create tasks
- Assign tasks to members
- Track progress

### Scenario 3: Member Task Management
- Login as `mike.wilson`
- View assigned tasks
- Update task status
- Add comments
- Upload attachments
- View project details (assigned projects only)

---

**Last Updated:** 2025-01-15

