<?php
session_start();
require_once 'db.php';

$error = '';

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    echo "<script>window.location.href = 'index.php';</script>";
    exit;
}

// Process login form
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Validation
    if(empty($username) || empty($password)) {
        $error = "Both username and password are required";
    } else {
        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verify password
                if(password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    
                    // Redirect to homepage
                    echo "<script>window.location.href = 'index.php';</script>";
                    exit;
                } else {
                    $error = "Invalid password";
                }
            } else {
                $error = "User not found";
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
    <title>Login - YouTubeClone</title>
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
        
        .login-container {
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
        
        .login-btn {
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
        
        .login-btn:hover {
            background-color: #d40000;
        }
        
        .signup-link {
            text-align: center;
            margin-top: 20px;
            color: #606060;
            font-size: 14px;
        }
        
        .signup-link a {
            color: #1a73e8;
            text-decoration: none;
        }
        
        .signup-link a:hover {
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
    
    <div class="login-container">
        <div class="logo">
            <i class="fab fa-youtube"></i>
            <h1>Login to YouTubeClone</h1>
        </div>
        
        <?php if(!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form action="login.php" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            
            <button type="submit" class="login-btn">Login</button>
        </form>
        
        <div class="signup-link">
            Don't have an account? <a href="signup.php">Sign Up</a>
        </div>
    </div>
</body>
</html>
