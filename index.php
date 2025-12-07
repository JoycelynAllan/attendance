<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management System</title>
    <link rel="stylesheet" href="requests/css/style.css">
    <style>
        .landing-container {
            text-align: center;
            max-width: 600px;
        }
        
        .landing-container h1 {
            font-size: 42px;
            color: var(--white);
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            font-weight: 700;
        }
        
        .landing-container p {
            font-size: 18px;
            color: var(--white);
            margin-bottom: 40px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }
        
        .button-group {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .landing-btn {
            padding: 18px 40px;
            font-size: 18px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            min-width: 200px;
        }
        
        .landing-btn-primary {
            background: var(--white);
            color: var(--wine-primary);
        }
        
        .landing-btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 255, 255, 0.4);
            background: var(--off-white);
        }
        
        .landing-btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: var(--white);
            border: 2px solid var(--white);
        }
        
        .landing-btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 255, 255, 0.3);
        }
        
        .features {
            margin-top: 60px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }
        
        .feature-item {
            background: var(--white);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .feature-item h3 {
            color: var(--wine-primary);
            font-size: 16px;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .feature-item p {
            color: var(--text-dark);
            font-size: 14px;
            margin: 0;
            text-shadow: none;
        }
    </style>
</head>
<body>
    <div class="landing-container">
        <h1>📋 Attendance Management</h1>
        <p>Streamline your attendance tracking and course management with our comprehensive system</p>
        
        <div class="button-group">
            <a href="login.html" class="landing-btn landing-btn-primary">Login</a>
            <a href="signup.html" class="landing-btn landing-btn-secondary">Register</a>
        </div>
        
        <div class="features">
            <div class="feature-item">
                <h3>📚 Course Management</h3>
                <p>Create and manage courses easily</p>
            </div>
            <div class="feature-item">
                <h3>✅ Attendance Tracking</h3>
                <p>Track student attendance efficiently</p>
            </div>
            <div class="feature-item">
                <h3>👥 Enrollment System</h3>
                <p>Manage student enrollments seamlessly</p>
            </div>
        </div>
    </div>
</body>
</html>