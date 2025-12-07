# Attendance Management System

A comprehensive web-based attendance management system built with PHP, MySQL, and JavaScript. This system allows faculty and interns to manage class sessions and track student attendance, while students can check in using codes and view their attendance reports.

## 📋 Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage Guide](#usage-guide)
- [File Structure](#file-structure)
- [Database Schema](#database-schema)
- [Security](#security)
- [Troubleshooting](#troubleshooting)

## ✨ Features

### For Faculty & Faculty Interns:
- ✅ Create and manage class sessions with automatic attendance code generation
- ✅ Mark attendance manually (Present, Late, Absent)
- ✅ View enrolled students and their attendance status
- ✅ Manage course enrollment requests
- ✅ View session attendance statistics

### For Students:
- ✅ Check in using 6-digit attendance codes
- ✅ View overall attendance reports with percentages
- ✅ View daily attendance reports
- ✅ Browse and enroll in available courses
- ✅ Track pending enrollment requests

### System Features:
- 🔐 Role-based access control (Faculty, Intern, Student)
- 🔄 Real-time attendance tracking
- 📊 Comprehensive attendance reporting
- ⏰ Automatic late detection (15 minutes after session start)
- 🔑 Time-limited attendance codes (expire 2 hours after session end)
- 📱 Responsive design

## 📦 Requirements

- **Web Server**: Apache 2.4+ (with mod_rewrite enabled)
- **PHP**: 7.4 or higher
- **Database**: MySQL 5.7+ or MariaDB 10.2+
- **Browser**: Modern browser with JavaScript enabled

## 🚀 Installation

### Step 1: Clone/Download the Project

Place the project files in your web server directory:
- **XAMPP**: `C:\xampp\htdocs\attendance system\`
- **Linux Apache**: `/var/www/html/attendance-system/`
- **Custom**: Your web server's document root

### Step 2: Database Setup

1. Create a MySQL database:
   ```sql
   CREATE DATABASE attendancemanagement;
   ```

2. Import the database schema:
   - Open phpMyAdmin or MySQL command line
   - Import `attendancemanagement.sql` file
   - This will create all necessary tables

3. Verify required tables exist:
   - `users` - User accounts
   - `courses` - Course information
   - `Enrollment` - Student enrollments
   - `sessions` - Class sessions
   - `attendance` - Attendance records

### Step 3: Configure Database Connection

1. Create a `.env` file in the project root:
   ```env
   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=attendancemanagement
   DB_USER=root
   DB_PASS=
   ```

2. **For XAMPP**: Leave `DB_PASS` empty (default root has no password)
   ```env
   DB_PASS=
   ```

3. **For Production**: Use secure credentials:
   ```env
   DB_HOST=your_host
   DB_PORT=3306
   DB_NAME=attendancemanagement
   DB_USER=your_username
   DB_PASS=your_secure_password
   ```

### Step 4: Set File Permissions

Ensure Apache can read/write to the directory:
```bash
chmod 755 -R /path/to/attendance-system
```

### Step 5: Access the Application

Open your browser and navigate to:
- **Local**: `http://localhost/attendance%20system/`
- **Server**: `http://169.239.251.102:341/attendance-system/`

## ⚙️ Configuration

### Apache Configuration

The `.htaccess` file is already configured with:
- Security headers (XSS protection, frame options)
- File access restrictions (protects `.env` files)
- Character encoding (UTF-8)
- File upload limits (10MB)

### PHP Configuration

Ensure these PHP settings are enabled:
- `PDO` extension
- `pdo_mysql` extension
- `mod_rewrite` (for Apache)

## 📖 Usage Guide

### For Faculty/Interns:

#### Creating a Session:
1. Login to your dashboard
2. Navigate to **"Sessions"** tab
3. Select a course from the dropdown
4. Click **"Create New Session"**
5. Fill in:
   - Date
   - Start time
   - End time
   - Topic (optional)
   - Location (optional)
6. Click **"Create Session"**
7. A 6-digit attendance code will be generated automatically
8. Share this code with students

#### Marking Attendance:
1. Go to **"Sessions"** tab
2. Select a course
3. Click **"Mark Attendance"** on a session
4. Select status for each student:
   - **Present** - Student attended on time
   - **Late** - Student arrived late
   - **Absent** - Student did not attend
5. Click **"Save Attendance"**

#### Viewing Enrolled Students:
1. Click on any course in **"My Courses"** section
2. View all enrolled students
3. See faculty and intern information
4. View attendance status for each student

### For Students:

#### Checking In with Code:
1. Login to student dashboard
2. Go to **"Check In with Code"** section
3. Click **"Check In with Code"** button
4. Enter the 6-digit code provided by your instructor
5. System will automatically:
   - Mark you as **Present** (if within 15 minutes of start)
   - Mark you as **Late** (if after 15 minutes)
   - Show error if code is invalid/expired

#### Viewing Attendance Reports:
1. Go to **"Attendance Reports"** section
2. Select a course from dropdown
3. Choose report type:
   - **Overall Report**: Shows total attendance percentage, session breakdown
   - **Daily Report**: Shows attendance for a specific date
4. View detailed statistics and session history

#### Enrolling in Courses:
1. Go to **"Available Courses"** section
2. Browse available courses
3. Click **"Join Course"** on a course
4. Select enrollment type:
   - **Regular** - Full enrollment
   - **Auditor** - Audit enrollment
   - **Observer** - Observer status
5. Wait for faculty approval
6. Check **"Pending Requests"** for status

## 📁 File Structure

```
attendance-system/
│
├── .env                          # Database configuration (create this)
├── .htaccess                     # Apache configuration
│
├── Core Files
│   ├── index.php                 # Home/landing page
│   ├── login.php                 # Login handler
│   ├── login.html                # Login page
│   ├── signup.php                # Registration handler
│   ├── signup.html               # Registration page
│   ├── logout.php                # Logout handler
│   ├── auth_check.php            # Authentication middleware
│   └── db_connect.php            # Database connection
│
├── Dashboard Files
│   ├── faculty_dashboard.php     # Faculty dashboard
│   ├── faculty_intern_dashboard.php  # Intern dashboard
│   └── student_dashboard.php     # Student dashboard
│
├── API Endpoints
│   ├── create_course.php         # Create new course
│   ├── create_session.php        # Create class session
│   ├── get_courses.php           # Get courses (enrolled/available/pending)
│   ├── get_sessions.php          # Get sessions for a course
│   ├── get_enrolled_students.php # Get enrolled students
│   ├── get_attendance_report.php # Get attendance reports
│   ├── get_session_attendance.php # Get session attendance
│   ├── join_course.php           # Student enroll in course
│   ├── join_course_intern.php    # Intern join course
│   ├── manage_enrollment.php     # Approve/reject enrollments
│   ├── mark_attendance.php       # Mark attendance manually
│   └── check_in_code.php         # Student check-in with code
│
└── requests/
    ├── css/
    │   ├── style.css             # Main stylesheet
    │   └── dashboard.css         # Dashboard styles
    └── js/
        ├── login.js              # Login functionality
        ├── signup.js             # Registration functionality
        ├── faculty_dashboard.js  # Faculty dashboard logic
        ├── faculty_intern_dashboard.js  # Intern dashboard logic
        ├── student_dashboard.js  # Student dashboard logic
        ├── session_management.js  # Session management
        └── student_attendance.js # Student attendance features
```

## 🗄️ Database Schema

### Key Tables:

**users**
- `user_id` (Primary Key)
- `username`, `email`, `password`
- `first_name`, `last_name`
- `role` (faculty, intern, student)

**courses**
- `course_id` (Primary Key)
- `course_code`, `course_name`, `description`
- `faculty_id` (Foreign Key → users)
- `intern_id` (Foreign Key → users, nullable)

**Enrollment**
- `enrollment_id` (Primary Key)
- `student_id` (Foreign Key → users)
- `course_id` (Foreign Key → courses)
- `status` (pending, approved, rejected)
- `enrollment_type` (regular, auditor, observer)

**sessions**
- `session_id` (Primary Key)
- `course_id` (Foreign Key → courses)
- `date`, `start_time`, `end_time`
- `topic`, `location`
- `attendance_code` (6-digit code)
- `code_expires_at` (expiration datetime)
- `created_by` (Foreign Key → users)

**attendance**
- `attendance_id` (Primary Key)
- `session_id` (Foreign Key → sessions)
- `student_id` (Foreign Key → users)
- `status` (present, late, absent)
- `check_in_time`, `checked_in_at`
- `check_in_method` (manual, code)

## 🔒 Security

### Implemented Security Features:

1. **Password Hashing**: All passwords are hashed using PHP's `password_hash()`
2. **SQL Injection Protection**: All queries use PDO prepared statements
3. **Session Management**: Secure session handling with authentication checks
4. **File Protection**: `.htaccess` prevents access to sensitive files (`.env`, logs)
5. **Security Headers**: XSS protection, frame options, content type options
6. **Role-Based Access**: All endpoints verify user roles and permissions
7. **Input Validation**: Server-side validation for all user inputs

### Best Practices:

- ✅ Never commit `.env` file to version control
- ✅ Use strong passwords for database in production
- ✅ Keep PHP and MySQL updated
- ✅ Regularly backup your database
- ✅ Use HTTPS in production

## 🐛 Troubleshooting

### Database Connection Errors:

**Error**: `Access denied for user 'root'@'localhost'`
- **Solution**: Check your `.env` file credentials
- For XAMPP: Ensure `DB_PASS=` is empty
- Restart Apache after changing `.env`

**Error**: `Unknown column 'attendance_code'`
- **Solution**: Database schema needs to be updated
- Ensure all tables have required columns (check `attendancemanagement.sql`)

### Session/Code Issues:

**Attendance codes not showing**:
- Check if session was created successfully
- Verify `sessions` table has `attendance_code` column
- Check browser console for JavaScript errors

**Codes expired immediately**:
- Codes expire 2 hours after session end time
- Create a new session if needed

### File Access Issues:

**404 Not Found errors**:
- Check Apache `mod_rewrite` is enabled
- Verify `.htaccess` file exists
- Check file permissions

**CSS/JS not loading**:
- Check browser console for errors
- Verify file paths are correct
- Ensure `.htaccess` allows access to static files

### General Issues:

**Page shows blank/white screen**:
- Enable PHP error reporting in `db_connect.php` (temporarily)
- Check PHP error logs
- Verify database connection

**Login not working**:
- Check session is starting correctly
- Verify password hashing matches
- Check `auth_check.php` is included

## 📝 Notes

- Attendance codes are **6-digit random numbers**
- Codes **expire 2 hours after session end time**
- Students can only check in **once per session**
- Late check-ins are automatically detected (after 15 minutes)
- Pending enrollment requests are automatically removed when approved

## 📞 Support

For issues or questions:
1. Check the troubleshooting section above
2. Review browser console for JavaScript errors
3. Check PHP error logs
4. Verify database connection and schema

## 📄 License

This project is provided as-is for educational and internal use.

---

**Last Updated**: 2024
**Version**: 1.0
