<?php
// Initialize the session
session_start();

// Check if the user is already logged in, if yes then redirect to the appropriate page
if (isset($_SESSION["user_id"])) {
    // If the user is an administrator, redirect to admin panel
    if (isset($_SESSION["is_admin"]) && $_SESSION["is_admin"] === true) {
        header("location: admin_panel.php");
        exit;
    } else {
        // Regular user, redirect to dashboard
        header("location: index.php");
        exit;
    }
}

// Include config file
require_once "config/database.php";
require_once 'config/connect.php';
require_once "models/User.php";

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $login_err = "";
$signup_success = "";

// Check for success message from signup
if (isset($_SESSION['signup_success'])) {
    $signup_success = $_SESSION['signup_success'];
    unset($_SESSION['signup_success']);
}

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Check if username is empty
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }

    // Check if password is empty
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    // Validate credentials
    if (empty($username_err) && empty($password_err)) {
        // Create database connection
        $database = new Database();
        $db = $database->getConnection();

        // Initialize user object
        $user = new User($db);
        $user->username = $username;
        $user->password = $password;

        // Attempt to login
        if ($user->login()) {
            // Redirect user to appropriate page based on role
            if (isset($_SESSION["is_admin"]) && $_SESSION["is_admin"] === true) {
                // Admin user - redirect to admin panel
                header("location: admin_panel.php");
            } else {
                // Regular user
                header("location: index.php");
            }
            exit;
        } else {
            // Username or password is incorrect
            $login_err = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="login.css">
    <link rel="icon" type="image/png" href="assets/b.png">
</head>

<body>
    <div class="login-container">
        <!-- Image panel on the left (order: 1) -->
        <div class="image-panel">
            <div class="branding-overlay">
                <h2 class="brand-logo" style="font-size: 37px;">Oyanib Shoes</h2>
                <p class="brand-tagline">Your journey begins with a single click</p>
            </div>
            <div class="bottom-illustration">
                <img src="assets/1.png" alt="logo" class="brand-logo" height="50">
            </div>
        </div>

        <!-- Login panel on the right (order: 2) -->
        <div class="login-panel">
            <div class="login-header">
                <h1 class="welcome-text">Welcome</h1>
                <p class="form-title">Sign in to access your account</p>
            </div>

            <?php
            if (!empty($login_err)) {
                echo '<div class="alert alert-danger"><i data-feather="alert-circle" class="feather-icon me-2"></i>' . $login_err . '</div>';
            }

            if (!empty($signup_success)) {
                echo '<div class="alert alert-success"><i data-feather="check-circle" class="feather-icon me-2"></i>' . $signup_success . '</div>';
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
                    <i data-feather="lock" class="input-icon"></i>
                    <input type="password" name="password" id="password" placeholder="Enter your password"
                        class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                    <button type="button" class="password-toggle" tabindex="-1" aria-label="Toggle password visibility">
                        <i data-feather="eye" id="togglePasswordIcon" class="toggle-icon"></i>
                    </button>
                    <?php if (!empty($password_err)): ?>
                        <div class="invalid-feedback">
                            <i data-feather="info" class="feather-icon me-1"></i><?php echo $password_err; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">
                    Login Now
                </button>

                <div class="form-footer">
                    Don't have an account? <a href="signin.php">Sign up for an Account</a>
                </div>

                <div class="feature-list">
                    <div class="feature-item">
                        <i data-feather="shield" class="feature-icon"></i>
                        Secure login protection
                    </div>
                    <div class="feature-item">
                        <i data-feather="user-check" class="feature-icon"></i>
                        Trusted by thousands
                    </div>
                    <div class="feature-item">
                        <i data-feather="headphones" class="feature-icon"></i>
                        24/7 support team
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize Feather icons
            feather.replace();

            const togglePassword = document.querySelector('.password-toggle');
            const passwordInput = document.querySelector('#password');
            const toggleIconContainer = document.querySelector('#togglePasswordIcon').parentNode;

            // Set initial state
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
        });
    </script>
</body>

</html>