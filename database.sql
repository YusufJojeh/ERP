-- Advanced Project & Task Management System Database Schema
-- Created for ERP system with complete sample data

CREATE DATABASE IF NOT EXISTS erp_task_management;
USE erp_task_management;

-- Users table for authentication and user management
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'project_manager', 'member') DEFAULT 'member',
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    avatar VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Projects table for project management
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    start_date DATE,
    end_date DATE,
    status ENUM('planning', 'active', 'on_hold', 'completed', 'cancelled') DEFAULT 'planning',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    manager_id INT,
    budget DECIMAL(10,2),
    progress INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Project members table for team assignments
CREATE TABLE project_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('manager', 'developer', 'designer', 'tester', 'observer') DEFAULT 'developer',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_project_user (project_id, user_id)
);

-- Tasks table for task management
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'review', 'completed', 'cancelled') DEFAULT 'pending',
    assigned_to INT,
    created_by INT NOT NULL,
    due_date DATE,
    completed_at TIMESTAMP NULL,
    estimated_hours DECIMAL(5,2),
    actual_hours DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Comments table for task discussions
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Activity logs table for tracking all system activities
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- File attachments table
CREATE TABLE attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT,
    project_id INT,
    user_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    related_entity_type VARCHAR(50),
    related_entity_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert sample data

-- Sample users
INSERT INTO users (username, email, password, role, first_name, last_name) VALUES
('admin', 'admin@erp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System', 'Administrator'),
('john.doe', 'john.doe@erp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'project_manager', 'John', 'Doe'),
('jane.smith', 'jane.smith@erp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'project_manager', 'Jane', 'Smith'),
('mike.wilson', 'mike.wilson@erp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member', 'Mike', 'Wilson'),
('sarah.jones', 'sarah.jones@erp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member', 'Sarah', 'Jones'),
('david.brown', 'david.brown@erp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member', 'David', 'Brown'),
('lisa.garcia', 'lisa.garcia@erp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member', 'Lisa', 'Garcia');

-- Sample projects
INSERT INTO projects (name, description, start_date, end_date, status, priority, manager_id, budget, progress) VALUES
('ERP System Development', 'Complete ERP system with modules for HR, Finance, and Operations', '2024-01-15', '2024-06-30', 'active', 'high', 2, 150000.00, 65),
('Website Redesign', 'Modern responsive website redesign with new features', '2024-02-01', '2024-04-15', 'active', 'medium', 3, 50000.00, 40),
('Mobile App Development', 'Cross-platform mobile application for customer management', '2024-03-01', '2024-08-31', 'planning', 'high', 2, 80000.00, 10),
('Database Migration', 'Migrate legacy database to new cloud infrastructure', '2024-01-01', '2024-03-31', 'completed', 'critical', 3, 25000.00, 100),
('Security Audit', 'Comprehensive security audit and vulnerability assessment', '2024-02-15', '2024-03-15', 'on_hold', 'high', 2, 15000.00, 30);

-- Sample project members
INSERT INTO project_members (project_id, user_id, role) VALUES
(1, 2, 'manager'),
(1, 4, 'developer'),
(1, 5, 'developer'),
(1, 6, 'designer'),
(1, 7, 'tester'),
(2, 3, 'manager'),
(2, 4, 'developer'),
(2, 5, 'designer'),
(3, 2, 'manager'),
(3, 6, 'developer'),
(3, 7, 'developer'),
(4, 3, 'manager'),
(4, 4, 'developer'),
(5, 2, 'manager'),
(5, 5, 'tester');

-- Sample tasks
INSERT INTO tasks (project_id, title, description, priority, status, assigned_to, created_by, due_date, estimated_hours) VALUES
(1, 'Design Database Schema', 'Create comprehensive database schema for all ERP modules', 'high', 'completed', 4, 2, '2024-01-25', 16.0),
(1, 'Implement User Authentication', 'Build secure login system with role-based access control', 'critical', 'in_progress', 4, 2, '2024-02-15', 24.0),
(1, 'Create Dashboard Interface', 'Design and implement main dashboard with analytics', 'high', 'pending', 5, 2, '2024-02-28', 20.0),
(1, 'HR Module Development', 'Build human resources management module', 'medium', 'pending', 6, 2, '2024-03-15', 40.0),
(1, 'Finance Module Development', 'Create financial management and reporting module', 'high', 'pending', 7, 2, '2024-03-30', 35.0),
(2, 'UI/UX Design', 'Create modern and responsive design mockups', 'high', 'completed', 5, 3, '2024-02-10', 12.0),
(2, 'Frontend Development', 'Implement responsive frontend using Bootstrap 5', 'medium', 'in_progress', 4, 3, '2024-03-01', 30.0),
(2, 'Backend API Development', 'Build RESTful API for website functionality', 'medium', 'pending', 6, 3, '2024-03-15', 25.0),
(3, 'Project Planning', 'Define requirements and create project roadmap', 'high', 'in_progress', 2, 2, '2024-03-15', 8.0),
(3, 'Technology Stack Selection', 'Choose appropriate technologies for mobile development', 'medium', 'pending', 6, 2, '2024-03-20', 4.0),
(4, 'Data Backup', 'Create complete backup of existing database', 'critical', 'completed', 4, 3, '2024-01-05', 4.0),
(4, 'Migration Scripts', 'Develop scripts for data migration', 'high', 'completed', 4, 3, '2024-01-20', 16.0),
(4, 'Testing and Validation', 'Test migrated data and validate integrity', 'high', 'completed', 5, 3, '2024-02-15', 12.0),
(5, 'Vulnerability Assessment', 'Scan system for security vulnerabilities', 'critical', 'completed', 5, 2, '2024-02-20', 8.0),
(5, 'Penetration Testing', 'Conduct comprehensive penetration testing', 'high', 'pending', 5, 2, '2024-03-01', 16.0);

-- Sample comments
INSERT INTO comments (task_id, user_id, comment, is_internal) VALUES
(1, 4, 'Database schema completed successfully. All tables created with proper relationships.', FALSE),
(1, 2, 'Great work! The schema looks comprehensive and well-structured.', FALSE),
(2, 4, 'Authentication system is 80% complete. Working on session management now.', FALSE),
(2, 2, 'Please ensure all security best practices are implemented.', FALSE),
(3, 5, 'Starting work on dashboard design. Will share mockups soon.', FALSE),
(6, 5, 'UI/UX design completed. All mockups approved by stakeholders.', FALSE),
(6, 3, 'Excellent work! The design looks modern and professional.', FALSE),
(7, 4, 'Frontend development in progress. Bootstrap 5 integration going smoothly.', FALSE),
(9, 2, 'Project planning phase started. Gathering requirements from stakeholders.', FALSE),
(11, 4, 'Database backup completed successfully. All data secured.', FALSE),
(14, 5, 'Vulnerability assessment completed. Found 3 minor issues that need attention.', FALSE);

-- Sample activity logs
INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address) VALUES
(2, 'created', 'project', 1, 'Created new project: ERP System Development', '192.168.1.100'),
(3, 'created', 'project', 2, 'Created new project: Website Redesign', '192.168.1.101'),
(4, 'completed', 'task', 1, 'Completed task: Design Database Schema', '192.168.1.102'),
(5, 'completed', 'task', 6, 'Completed task: UI/UX Design', '192.168.1.103'),
(2, 'assigned', 'task', 2, 'Assigned task to Mike Wilson', '192.168.1.100'),
(3, 'assigned', 'task', 7, 'Assigned task to Mike Wilson', '192.168.1.101'),
(4, 'updated', 'task', 2, 'Updated task status to in_progress', '192.168.1.102'),
(5, 'commented', 'task', 1, 'Added comment to task', '192.168.1.103'),
(6, 'joined', 'project', 1, 'Joined project as developer', '192.168.1.104'),
(7, 'joined', 'project', 1, 'Joined project as tester', '192.168.1.105');

-- Sample notifications
INSERT INTO notifications (user_id, title, message, type, related_entity_type, related_entity_id) VALUES
(4, 'New Task Assignment', 'You have been assigned a new task: Implement User Authentication', 'info', 'task', 2),
(5, 'New Task Assignment', 'You have been assigned a new task: Create Dashboard Interface', 'info', 'task', 3),
(6, 'New Task Assignment', 'You have been assigned a new task: HR Module Development', 'info', 'task', 4),
(7, 'New Task Assignment', 'You have been assigned a new task: Finance Module Development', 'info', 'task', 5),
(2, 'Task Completed', 'Mike Wilson completed task: Design Database Schema', 'success', 'task', 1),
(3, 'Task Completed', 'Sarah Jones completed task: UI/UX Design', 'success', 'task', 6),
(4, 'Comment Added', 'John Doe added a comment to your task', 'info', 'task', 2),
(5, 'Comment Added', 'Jane Smith added a comment to your task', 'info', 'task', 6);

-- Create indexes for better performance
CREATE INDEX idx_tasks_project_id ON tasks(project_id);
CREATE INDEX idx_tasks_assigned_to ON tasks(assigned_to);
CREATE INDEX idx_tasks_status ON tasks(status);
CREATE INDEX idx_tasks_due_date ON tasks(due_date);
CREATE INDEX idx_comments_task_id ON comments(task_id);
CREATE INDEX idx_activity_logs_user_id ON activity_logs(user_id);
CREATE INDEX idx_activity_logs_entity ON activity_logs(entity_type, entity_id);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_is_read ON notifications(is_read);
