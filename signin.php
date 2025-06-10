<?php
// Initialize the session
session_start();

// Check if the user is already logged in, if yes then redirect to dashboard
if (isset($_SESSION["user_id"])) {
    header("location: index.php");
    exit;
}

// Include config file
require_once "config/database.php";
require_once 'config/connect.php';
require_once "models/User.php";

// Define variables and initialize with empty values
$username = $email = $password = $confirm_password = "";
$username_err = $email_err = $password_err = $confirm_password_err = $signup_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if username is empty
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        $username = trim($_POST["username"]);
        
        // Create database connection
        $database = new Database();
        $db = $database->getConnection();
        
        // Initialize user object
        $user = new User($db);
        
        // Check if username exists
        if ($user->usernameExists($username)) {
            $username_err = "This username is already taken.";
        }
    }
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        $email = trim($_POST["email"]);
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_err = "Please enter a valid email address.";
        } else {
            // Create database connection if not already created
            if (!isset($db)) {
                $database = new Database();
                $db = $database->getConnection();
                $user = new User($db);
            }
            
            // // Check if email already exists
            // if ($user->emailExists($email)) {
            //     $email_err = "This email is already registered.";
            // }
        }
    }
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";     
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Check input errors before inserting in database
    if (empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err)) {
        
        // Create database connection if not already created
        if (!isset($db)) {
            $database = new Database();
            $db = $database->getConnection();
            $user = new User($db);
        }
        
        // Set user properties
        $user->username = $username;
        $user->email = $email;
        $user->password = $password;
        // Role ID 5 is set directly in the User.php register() method
        
        // Register the user
        if ($user->register()) {
            // Registration successful, redirect to login page with success message
            $_SESSION['signup_success'] = "Account created successfully! You can now login.";
            header("location: login.php");
            exit;
        } else {
            $signup_err = "Something went wrong. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="signin.css">
    <link rel="icon" type="image/png" href="assets/b.png">
</head>

<body>
    <div class="login-container">
        <!-- Left side login panel -->
        <div class="login-panel">
            <div class="login-header">
                <h1 class="welcome-text">Create An Account</h1>
                <p class="form-title">Join us to start your journey</p>
            </div>

            <?php
            if (!empty($signup_err)) {
                echo '<div class="alert alert-danger"><i data-feather="alert-circle" class="feather-icon me-2"></i>' . $signup_err . '</div>';
            }
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" autocomplete="off">
                <div class="input-group">
                    <i data-feather="user" class="input-icon"></i>
                    <input type="text" name="username" id="username" placeholder="Enter your username"
                        class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>"
                        value="<?php echo $username; ?>">
                    <?php if (!empty($username_err)): ?>
                        <div class="invalid-feedback"><i data-feather="info"
                                class="feather-icon me-1"></i><?php echo $username_err; ?></div>
                    <?php endif; ?>
                </div>

                <div class="input-group">
                    <i data-feather="mail" class="input-icon"></i>
                    <input type="email" name="email" id="email" placeholder="Enter your email"
                        class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>"
                        value="<?php echo $email; ?>">
                    <?php if (!empty($email_err)): ?>
                        <div class="invalid-feedback"><i data-feather="info"
                                class="feather-icon me-1"></i><?php echo $email_err; ?></div>
                    <?php endif; ?>
                </div>

                <div class="input-group">
                    <i data-feather="lock" class="input-icon"></i>
                    <input type="password" name="password" id="password" placeholder="Enter your password"
                        class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                    <button type="button" class="password-toggle" tabindex="-1"
                        aria-label="Toggle password visibility">
                        <i data-feather="eye" id="togglePasswordIcon" class="toggle-icon"></i>
                    </button>
                    <?php if (!empty($password_err)): ?>
                        <div class="invalid-feedback"><i data-feather="info"
                                class="feather-icon me-1"></i><?php echo $password_err; ?></div>
                    <?php endif; ?>
                </div>

                <div class="input-group">
                    <i data-feather="check-circle" class="input-icon"></i>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm your password"
                        class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                    <button type="button" class="confirm-password-toggle" tabindex="-1"
                        aria-label="Toggle confirm password visibility">
                        <i data-feather="eye" id="toggleConfirmPasswordIcon" class="toggle-icon"></i>
                    </button>
                    <?php if (!empty($confirm_password_err)): ?>
                        <div class="invalid-feedback"><i data-feather="info"
                                class="feather-icon me-1"></i><?php echo $confirm_password_err; ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">
                    Create Account
                </button>

                <div class="form-footer">
                    Already have an account? <a href="login.php">Log in</a>
                </div>
                
                <div class="feature-list">
                    <div class="feature-item">
                        <i data-feather="shield" class="feature-icon"></i>
                        Secure registration
                    </div>
                    <div class="feature-item">
                        <i data-feather="user-plus" class="feature-icon"></i>
                        Join our community
                    </div>
                    <div class="feature-item">
                        <i data-feather="globe" class="feature-icon"></i>
                        Access worldwide tours
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Right side image panel -->
        <div class="image-panel">
            <div class="branding-overlay">
                <h2 class="brand-logo" style="font-size: 37px;">Oyanib Shoes</h2>
                <p class="brand-tagline">Your journey begins with a single click</p>
            </div>
            <div class="bottom-illustration">
            <img src="assets/1.png" alt="logo" class="brand-logo" height="50">
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize Feather icons
            feather.replace();

            // Password toggle functionality
            const togglePassword = document.querySelector('.password-toggle');
            const passwordInput = document.querySelector('#password');
            const toggleIconContainer = document.querySelector('#togglePasswordIcon').parentNode;

            // Set initial state for password
            let passwordVisible = false;

            if (togglePassword && passwordInput && toggleIconContainer) {
                togglePassword.addEventListener('click', function () {
                    // Toggle password visibility state
                    passwordVisible = !passwordVisible;

                    // Change password input type
                    passwordInput.type = passwordVisible ? 'text' : 'password';

                    // Completely replace the icon by updating the HTML with color class
                    toggleIconContainer.innerHTML = passwordVisible
                        ? '<i data-feather="eye-off" id="togglePasswordIcon" class="toggle-icon toggle-icon-visible"></i>'
                        : '<i data-feather="eye" id="togglePasswordIcon" class="toggle-icon"></i>';

                    // Re-initialize the new icon
                    feather.replace();
                });
            }

            // Confirm Password toggle functionality
            const toggleConfirmPassword = document.querySelector('.confirm-password-toggle');
            const confirmPasswordInput = document.querySelector('#confirm_password');
            const toggleConfirmIconContainer = document.querySelector('#toggleConfirmPasswordIcon').parentNode;

            // Set initial state for confirm password
            let confirmPasswordVisible = false;

            if (toggleConfirmPassword && confirmPasswordInput && toggleConfirmIconContainer) {
                toggleConfirmPassword.addEventListener('click', function () {
                    // Toggle confirm password visibility state
                    confirmPasswordVisible = !confirmPasswordVisible;

                    // Change confirm password input type
                    confirmPasswordInput.type = confirmPasswordVisible ? 'text' : 'password';

                    // Completely replace the icon by updating the HTML with color class
                    toggleConfirmIconContainer.innerHTML = confirmPasswordVisible
                        ? '<i data-feather="eye-off" id="toggleConfirmPasswordIcon" class="toggle-icon toggle-icon-visible"></i>'
                        : '<i data-feather="eye" id="toggleConfirmPasswordIcon" class="toggle-icon"></i>';

                    // Re-initialize the new icon
                    feather.replace();
                });
            }
        });
    </script>
</body>

</html>