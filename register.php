<?php
// register.php - User Registration System
require_once 'db.php';
require_once 'terms.php'; // Included to eventually use custom terms if needed

// --- REGISTRATION LOCK CHECK ---
$reg_setting = $pdo->query("SELECT setting_value FROM platform_settings WHERE setting_key = 'registration_open'")->fetchColumn(); 
if ($reg_setting == '0') { 
    die("<div style='font-family: Arial; text-align: center; margin-top: 50px; color: #555;'>
            <h2>Registration Closed</h2>
            <p>We are not currently accepting new player accounts. Please check back later.</p>
            <a href='index.php' style='color: #18bc9c; text-decoration: none; font-weight: bold;'>Return to Home</a>
         </div>");

$error = '';
$success = '';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    // Basic validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif ($password !== $password_confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = "Username or email is already taken.";
        } else {
            // Hash the password securely
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user into the database (default role is 'user')
            $insert_stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            
            try {
                $insert_stmt->execute([$username, $email, $hashed_password]);
                $success = "Registration successful! You can now log in.";
                // In a production environment, you would redirect to login.php here
                // header("Location: login.php");
                // exit;
            } catch (PDOException $e) {
                $error = "Registration failed due to a system error. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - MOWS</title>
	<!-- Link to global styles -->
    <link rel="stylesheet" href="style.css">
    
    <!-- Dynamic Theme Injection -->
    <style>
        :root {
            --bg-color: <?php echo htmlspecialchars($bg_color); ?>;
            --text-color: <?php echo htmlspecialchars($text_color); ?>;
            --primary-color: <?php echo htmlspecialchars($primary_color); ?>;
            --accent-color: <?php echo htmlspecialchars($accent_color); ?>;
        }
    </style>
</head>
<body class="auth-page">

<div class="login-box">
    <h2>Create an Account</h2>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?> <br><br> <a href="login.php">Go to Login</a></div>
    <?php else: ?>
        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="password_confirm">Confirm Password</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
            </div>
            
            <button type="submit">Register</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>