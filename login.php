<?php
session_start();

// If user is already logged in, redirect to dashboard

// Database configuration - Consider moving to a separate config file
$host = 'localhost';
$dbname = 'kenefinal';
$db_username = 'rehmanshareef';
$db_password = 'Shareef@1';

// Define target pages for each plan

// Establish database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Connection failed. Please try again later.");
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        // Fetch user details and active plan
        $stmt = $pdo->prepare("
            SELECT u.UserID, u.Username, u.PasswordHash, p.PlanName, p.EndDate 
            FROM UserDetails u 
            LEFT JOIN Plan p ON u.UserID = p.UserID 
            WHERE u.Username = :username 
            AND p.Status = 'active'
            AND p.EndDate >= CURRENT_DATE
            ORDER BY p.EndDate DESC
            LIMIT 1
        ");
        
        try {
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['PasswordHash'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['UserID'];
                $_SESSION['username'] = $user['Username'];
                $_SESSION['user_plan'] = $user['PlanName'];
                $_SESSION['last_activity'] = time();
                
                // Optional: Update last login timestamp
                
                header("Location: dashboard.php");
                exit();
            } else {
                // Use a generic error message for security
                $error_message = "Invalid username or password";
                // Add delay to prevent brute force attacks
                sleep(1);
            }
        } catch(PDOException $e) {
            error_log("Login query error: " . $e->getMessage());
            $error_message = "An error occurred during login" . $e->getMessage();
        }
    } else {
        $error_message = "Please enter both username and password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <img src="Knxt_Shield_Logo_003C_T.png" alt="Company Logo" class="logo">
        <h2>LOGIN</h2>
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="input-group">
                <i class="fa-solid fa-person"></i>
                <input type="text" name="username" placeholder="Username" required>
            </div>
            <div class="input-group">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Remember Me</label>
            </div>
            <button type="submit">Submit</button>
            <a href="forgotpassword.html">Forgot Password?</a>
            <div class="signup">
                <p><i>Not yet registered?</i> <a href="re1.php"><b>SIGN UP HERE</b></a></p>
            </div>
        </form>
    </div>
</body>
</html>