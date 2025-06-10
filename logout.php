<?php
 require_once 'models/authentication.php';
// Store the username for the message
$username = "";
$profile_image = "assets/profile.png"; // Default profile image

if (isset($_SESSION["username"])) {
    $username = $_SESSION["username"];
    
    // Use the user's profile image if available
    if (isset($_SESSION["profile_image"]) && !empty($_SESSION["profile_image"])) {
        $profile_image = $_SESSION["profile_image"];
    }
}

// Unset all of the session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Store the logout status for display
$logout_status = true;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3da0f1;
            --primary-light: #70aaf7;
            --primary-lighter: #e6f0fd;
            --white: #ffffff;
            --light-color: #f8fafc;
        }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: linear-gradient(rgba(27, 12, 243, 0.85), rgba(206, 221, 240, 0.767)), url('assets/NORSU.jpg');
            overflow: hidden;
            position: relative;
            background-size: cover;
            background-position: center;
        }

        .page-container {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 100vh;
            padding: 2rem;
            position: relative;
            z-index: 5;
        }

        .logout-container {
            text-align: center;
            padding: 3.5rem;
            border-radius: 24px;
            background: linear-gradient(145deg, #3da0f1 0%, #4e9eec 100%);
            color: white;
            box-shadow: 
                0 10px 30px rgba(61, 160, 241, 0.2),
                0 5px 15px rgba(61, 160, 241, 0.1),
                inset 0 1px 1px rgba(255, 255, 255, 0.2);
            max-width: 500px;
            width: 100%;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            z-index: 2;
            margin: 0 auto;
            backdrop-filter: blur(10px);
        }

        .logout-container:hover {
            transform: translateY(-5px);
            box-shadow: 
                0 15px 35px rgba(61, 160, 241, 0.25),
                0 5px 15px rgba(61, 160, 241, 0.15),
                inset 0 1px 1px rgba(255, 255, 255, 0.3);
        }

        .icon-container {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(145deg, #70aaf7 0%, #5a9df0 100%);
            margin: 0 auto 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 
                0 8px 16px rgba(0, 0, 0, 0.1),
                inset 0 -2px 5px rgba(0, 0, 0, 0.05),
                inset 0 2px 5px rgba(255, 255, 255, 0.2);
            overflow: hidden; /* To ensure the profile image stays within the circle */
        }

        .icon-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: drop-shadow(0 2px 3px rgba(0, 0, 0, 0.1));
        }

        .logout-message {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            letter-spacing: -0.5px;
        }

        .logout-subtext {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 300;
            margin-bottom: 2.5rem;
        }

        .logout-spinner {
            margin: 2rem auto;
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255, 255, 255, 0.2);
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 1.2s cubic-bezier(0.68, -0.55, 0.27, 1.55) infinite;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }

        .redirect-text {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-weight: 400;
        }

        .redirect-text i {
            font-size: 0.85rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .fade-out {
            animation: fadeOut 1.5s ease forwards;
        }

        @keyframes fadeOut {
            0% { opacity: 1; transform: translateY(0); }
            100% { opacity: 0; transform: translateY(-30px); }
        }

        /* Decorative background elements */
        .bg-shape {
            position: fixed;
            z-index: 1;
            border-radius: 50%;
            filter: blur(80px);
        }

        .bg-shape-1 {
            width: 400px;
            height: 400px;
            background-color: rgba(61, 160, 241, 0.15);
            top: -200px;
            right: -100px;
        }

        .bg-shape-2 {
            width: 300px;
            height: 300px;
            background-color: rgba(112, 170, 247, 0.12);
            bottom: -150px;
            left: -100px;
        }
        
        .bg-shape-3 {
            width: 200px;
            height: 200px;
            background-color: rgba(61, 160, 241, 0.08);
            top: 50%;
            right: 15%;
        }
    </style>
</head>

<body>
    <!-- Background decorative elements -->
    <div class="bg-shape bg-shape-1"></div>
    <div class="bg-shape bg-shape-2"></div>
    <div class="bg-shape bg-shape-3"></div>

    <div class="page-container">
        <div class="logout-container">
            <div class="icon-container">
                <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="User profile">
            </div>
            <h2 class="logout-message">
                <?php echo !empty($username) ? "Goodbye, {$username}!" : "You've Been Logged Out"; ?>
            </h2>
            <p class="logout-subtext">Thanks for using our Inventory Management System</p>
            <div class="logout-spinner"></div>
            <p class="redirect-text">
                Redirecting you to the login page...
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script>
        // Initialize Feather icons
        document.addEventListener('DOMContentLoaded', function () {
            feather.replace();

            const container = document.querySelector('.logout-container');
            container.style.opacity = 0;
            container.style.transform = 'translateY(20px)';

            setTimeout(function () {
                container.style.transition = 'opacity 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275), transform 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
                container.style.opacity = 1;
                container.style.transform = 'translateY(0)';
            }, 100);
        });

        // Add fade-out animation after 2.5 seconds
        setTimeout(function () {
            document.querySelector('.logout-container').classList.add('fade-out');
            // Redirect to login page after fade-out animation
            setTimeout(function () {
                window.location.href = "login.php";
            }, 1400);
        }, 2500);

        // Initialize Feather icons with custom size
        feather.replace({
            width: 72,
            height: 72
        });
    </script>
</body>

</html>