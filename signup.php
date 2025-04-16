<?php
session_start();
require_once 'db.php';

$error = '';
$success = '';

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    echo "<script>window.location.href = 'index.php';</script>";
    exit;
}

// Process sign up form
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validation
    if(empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif(strlen($username) < 3) {
        $error = "Username must be at least 3 characters";
    } elseif(strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } elseif($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        // Check if username or email already exists
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if($stmt->rowCount() > 0) {
                $error = "Username or email already exists";
            } else {
                // Create new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $hashed_password]);
                
                $success = "Account created successfully! You can now login.";
                
                // Redirect to login page after 2 seconds
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 2000);
                </script>";
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - YouTubeClone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', Arial, sans-serif;
        }
        
        body {
            background-color: #f9f9f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        
        .signup-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 400px;
            padding: 30px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .logo i {
            color: #ff0000;
            font-size: 40px;
            margin-bottom: 10px;
        }
        
        .logo h1 {
            color: #0a0a0a;
            font-size: 24px;
            font-weight: 500;
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #606060;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            border-color: #1a73e8;
            outline: none;
        }
        
        .signup-btn {
            background-color: #ff0000;
            color: white;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .signup-btn:hover {
            background-color: #d40000;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #606060;
            font-size: 14px;
        }
        
        .login-link a {
            color: #1a73e8;
            text-decoration: none;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .home-link {
            position: absolute;
            top: 20px;
            left: 20px;
            color: #606060;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-size: 14px;
        }
        
        .home-link i {
            margin-right: 5px;
        }
        
        .home-link:hover {
            color: #ff0000;
        }
    </style>
</head>
<body>
    <a href="index.php" class="home-link">
        <i class="fas fa-home"></i> Back to Home
    </a>
    
    <div class="signup-container">
        <div class="logo">
            <i class="fab fa-youtube"></i>
            <h1>Create Your Account</h1>
        </div>
        
        <?php if(!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form action="signup.php" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Create a password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
            </div>
            
            <button type="submit" class="signup-btn">Sign Up</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </div>
</body>
</html>
