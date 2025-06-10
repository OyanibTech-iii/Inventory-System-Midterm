<?php
// File: admin_panel.php
// Initialize the session
session_start();

if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true) {
    header("location: login.php");
    exit;
}


// Include config file
require_once "config/database.php";
require_once 'config/connect.php';
require_once "models/User.php";

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Initialize user object for managing users
$user = new User($db);

// Process user role changes if form is submitted
$update_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_user"])) {
    $user_id = $_POST["user_id"];
    $new_role = $_POST["new_role"];

    if ($user->updateUserRole($user_id, $new_role)) {
        $update_message = "User role updated successfully!";
    } else {
        $update_message = "Error updating user role.";
    }
}

// Get all users
$users = $user->getAllUsers();

// Get login history
$login_history = $user->getLoginHistory();

// Determine which section to display
$active_section = "dashboard"; // Default section
if (isset($_GET["section"])) {
    $active_section = $_GET["section"];
}

// Set view for dashboard subsections (default to info)
$dashboard_view = "info";
if (isset($_GET["view"]) && $_GET["view"] == "location") {
    $dashboard_view = "location";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/feather-icons"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <link rel="icon" type="image/png" href="assets/b.png">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-primary sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <img src="assets/1.png" alt="Company Logo" class="img-fluid"
                            style="max-width: 180px; height: 100px;">
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_section == 'dashboard') ? 'active' : ''; ?> text-white d-flex align-items-center py-2"
                                href="admin_panel.php?section=dashboard&view=info">
                                <!-- <i data-feather="activity" class="me-2"></i> -->
                                <span class="text-nowrap">Administrator</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_section == 'user-management') ? 'active' : ''; ?> text-white d-flex align-items-center py-2"
                                href="admin_panel.php?section=user-management">
                                <!-- <i data-feather="users" class="me-2"></i> -->
                                <span class="text-nowrap">User Management</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_section == 'login-history') ? 'active' : ''; ?> text-white d-flex align-items-center py-2"
                                href="admin_panel.php?section=login-history">
                                <!-- <i data-feather="clock" class="me-2"></i> -->
                                <span class="text-nowrap">Login History</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white d-flex align-items-center py-2" href="index.php">
                                <!-- <i data-feather="package" class="me-2"></i> -->
                                <span class="text-nowrap">Inventory</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($active_section == 'dashboard' && $dashboard_view == 'location') ? 'active' : ''; ?> text-white d-flex align-items-center py-2"
                                href="admin_panel.php?section=dashboard&view=location">
                                <!-- <i data-feather="map-pin" class="me-2"></i> -->
                                <span class="text-nowrap">Location</span>
                            </a>
                        </li>
                        <li class="nav-item mt-3 pt-10">
                            <a class="nav-link text-white d-flex align-items-center py-2" href="logout.php">
                                <!-- <i data-feather="log-out" class="me-2"></i> -->
                                <span class="text-nowrap">Logout</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div
                    class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Administrator Control Panel</h1>
                </div>

                <?php if (!empty($update_message)): ?>
                    <div class="alert alert-success" role="alert">
                        <i data-feather="check-circle" class="me-2"></i><?php echo $update_message; ?>
                    </div>
                <?php endif; ?>

                <!-- Dashboard Section with view toggle -->
                <?php if ($active_section == "dashboard"): ?>
                    <?php if ($dashboard_view == "info"): ?>

                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i data-feather="package" class="me-2"></i> Inventory System Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-4">
                                        <div class="card">
                                            <div class="card-body text-center">
                                                <img src="assets/ceo1.png" class="img-fluid rounded"
                                                    alt="Admin Profile Picture">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-8 mb-4">
                                        <div class="card">
                                            <div class="card-header bg-primary text-white">
                                                <h5 class="mb-0"><i data-feather="user" class="me-2"></i> Administrator
                                                    Profile</h5>
                                            </div>
                                            <div class="card-body">
                                                <h4>Pacifico M. Oyanib III</h4>
                                                <p class="text-muted"><i data-feather="briefcase" class="me-2"></i>System
                                                    Administrator</p>
                                                <hr>
                                                <p>With over 1 week of experience in inventory management systems, I oversee
                                                    all aspects of the platform including user permissions, stock
                                                    management, and system security. Also implemented the Database normalization
                                                    from 1NF,2NF and 3NF ensuring the data not redundant and properly flattened.
                                                </p>
                                               

                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header bg-light">
                                                <h5 class="mb-0">System Overview</h5>
                                            </div>
                                            <div class="card-body p-4">
                                                <div
                                                    class="welcome-banner bg-light p-4 rounded-3 mb-4 border-start border-primary border-5">
                                                    <h4 class="text-primary mb-3"><i data-feather="trending-up"
                                                            class="me-2"></i>Welcome to Your Admin
                                                        Dashboard</h4>
                                                    <p class="lead">Manage your inventory system efficiently with powerful
                                                        administrative tools at your fingertips.</p>
                                                </div>

                                                <div class="row mt-4">
                                                    <div class="col-md-4 mb-3">
                                                        <div
                                                            class="feature-card h-100 p-3 rounded-3 border-start border-success border-4 shadow-sm">
                                                            <h5 class="text-success"><i data-feather="settings"
                                                                    class="me-2"></i>User Management</h5>
                                                            <p class="mb-2">Create, modify, and manage user accounts and
                                                                permission levels for your team members.</p>
                                                            <a href="admin_panel.php?section=user-management"
                                                                class="btn btn-sm btn-outline-success">Manage Users <i
                                                                    data-feather="arrow-right" class="ms-1"></i></a>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4 mb-3">
                                                        <div
                                                            class="feature-card h-100 p-3 rounded-3 border-start border-info border-4 shadow-sm">
                                                            <h5 class="text-info"><i data-feather="shield"
                                                                    class="me-2"></i>Security Monitoring
                                                            </h5>
                                                            <p class="mb-2">Track login attempts, monitor system activity,
                                                                and maintain secure access controls.</p>
                                                            <a href="admin_panel.php?section=login-history"
                                                                class="btn btn-sm btn-outline-info">View History <i
                                                                    data-feather="arrow-right" class="ms-1"></i></a>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4 mb-3">
                                                        <div
                                                            class="feature-card h-100 p-3 rounded-3 border-start border-warning border-4 shadow-sm">
                                                            <h5 class="text-warning"><i data-feather="map-pin"
                                                                    class="me-2"></i>View Location</h5>
                                                            <p class="mb-2">Check the physical location of NORSU Dumaguete
                                                                Campus on an interactive map.</p>
                                                            <a href="admin_panel.php?section=dashboard&view=location"
                                                                class="btn btn-sm btn-outline-warning">View Map <i
                                                                    data-feather="arrow-right" class="ms-1"></i></a>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="text-center mt-3 text-muted">
                                                    <p><i data-feather="info" class="me-2"></i>Select any option from the
                                                        sidebar to navigate to different sections of the admin panel.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </section>
                    <?php else: ?>
                        <!-- Location View -->
                        <section id="dashboard-location" class="mb-5">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i data-feather="map-pin" class="me-2"></i> NORSU Dumaguete Campus Location
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="dashboard-content">
                                        <div class="map-fullscreen">
                                            <!-- Map container -->
                                            <div id="map"></div>

                                            <!-- Toggle button for location info (3 dots)
                                            <div class="location-toggle" id="locationToggle">
                                                <div class="dots">
                                                    <span></span>
                                                    <span></span>
                                                    <span></span>
                                                </div>
                                            </div> -->

                                            <!-- Location info panel (initially hidden) -->
                                            <div class="location-info-panel" id="locationInfoPanel">
                                                <div class="close-btn" id="closeInfoPanel">&times;</div>
                                                <h2>NORSU Dumaguete Campus</h2>
                                                <div class="location-info">
                                                    <h3>Visit Us Today!</h3>
                                                    <p><strong>Address:</strong> Kagawasan Avenue, Dumaguete City, Negros
                                                        Oriental, Philippines</p>
                                                    <p><strong>Phone:</strong> (035) 225-9177</p>
                                                    <p><strong>Hours:</strong></p>
                                                    <ul>
                                                        <li>Monday - Friday: 8:00 AM - 5:00 PM</li>
                                                        <li>Saturday: 8:00 AM - 12:00 PM</li>
                                                        <li>Sunday: Closed</li>
                                                    </ul>
                                                    <div class="directions-btn">
                                                        <a href="https://www.google.com/maps/dir//Negros+Oriental+State+University,+Kagawasan+Avenue,+Dumaguete+City,+Negros+Oriental/@9.3100649,123.3053896,17z/"
                                                            target="_blank" class="btn btn-location">Get Directions</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- User Management Section -->
                <?php if ($active_section == "user-management"): ?>
                    <section id="user-management" class="mb-5">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i data-feather="users" class="me-2"></i> User Management</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Username</th>
                                                <th>Email</th>
                                                <th>Current Role</th>
                                                <th>Created At</th>
                                                <th>Last Login</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($users): ?>
                                                <?php foreach ($users as $user_data): ?>
                                                    <tr>
                                                        <td><?php echo $user_data['user_id']; ?></td>
                                                        <td><?php echo htmlspecialchars($user_data['username']); ?></td>
                                                        <td><?php echo htmlspecialchars($user_data['email']); ?></td>
                                                        <td><?php echo htmlspecialchars($user_data['role_name']); ?></td>
                                                        <td><?php echo $user_data['created_at']; ?></td>
                                                        <td><?php echo $user_data['last_login'] ? $user_data['last_login'] : 'Never'; ?>
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-primary"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#editRoleModal<?php echo $user_data['user_id']; ?>">
                                                                <i data-feather="edit" class="me-1"></i> Change Role
                                                            </button>

                                                            <!-- Edit Role Modal -->
                                                            <div class="modal fade"
                                                                id="editRoleModal<?php echo $user_data['user_id']; ?>" tabindex="-1"
                                                                aria-labelledby="editRoleModalLabel" aria-hidden="true">
                                                                <div class="modal-dialog">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title" id="editRoleModalLabel">Change
                                                                                Role for
                                                                                <?php echo htmlspecialchars($user_data['username']); ?>
                                                                            </h5>
                                                                            <button type="button" class="btn-close"
                                                                                data-bs-dismiss="modal" aria-label="Close"></button>
                                                                        </div>
                                                                        <form method="POST"
                                                                            action="admin_panel.php?section=user-management">
                                                                            <div class="modal-body">
                                                                                <input type="hidden" name="user_id"
                                                                                    value="<?php echo $user_data['user_id']; ?>">
                                                                                <div class="mb-3">
                                                                                    <label for="new_role" class="form-label">Select
                                                                                        New Role</label>
                                                                                    <select class="form-select" name="new_role"
                                                                                        id="new_role">
                                                                                        <option value="1" <?php echo ($user_data['role_id'] == 1) ? 'selected' : ''; ?>>Admin</option>
                                                                                        <option value="2" <?php echo ($user_data['role_id'] == 2) ? 'selected' : ''; ?>>Manager</option>
                                                                                        <option value="3" <?php echo ($user_data['role_id'] == 3) ? 'selected' : ''; ?>>Sales Staff</option>
                                                                                        <option value="4" <?php echo ($user_data['role_id'] == 4) ? 'selected' : ''; ?>>Inventory Staff</option>
                                                                                    </select>
                                                                                </div>
                                                                            </div>
                                                                            <div class="modal-footer">
                                                                                <button type="button" class="btn btn-secondary"
                                                                                    data-bs-dismiss="modal">Cancel</button>
                                                                                <button type="submit" name="update_user"
                                                                                    class="btn btn-primary">Save Changes</button>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">No users found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Login History Section -->
                <?php if ($active_section == "login-history"): ?>
                    <section id="login-history">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i data-feather="clock" class="me-2"></i> User Login History</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>User ID</th>
                                                <th>Username</th>
                                                <th>Login Time</th>
                                                <th>IP Address</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($login_history): ?>
                                                <?php foreach ($login_history as $log): ?>
                                                    <tr>
                                                        <td><?php echo $log['user_id']; ?></td>
                                                        <td><?php echo htmlspecialchars($log['username']); ?></td>
                                                        <td><?php echo $log['login_time']; ?></td>
                                                        <td><?php echo $log['ip_address']; ?></td>
                                                        <td>
                                                            <?php if ($log['login_success']): ?>
                                                                <span class="badge"
                                                                    style="background-color: rgb(14, 160, 14);">Success</span>
                                                            <?php else: ?>
                                                                <span class="badge "
                                                                    style="background-color: rgb(204, 17, 17);">Failed</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">No login history found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Feather icons
        document.addEventListener('DOMContentLoaded', function () {
            feather.replace();

            // Initialize the location map if we're on that view
            if (document.getElementById('map')) {
                loadMapScript();

                // Set up event listeners for location panel
                document.getElementById('locationToggle').addEventListener('click', function () {
                    document.getElementById('locationInfoPanel').classList.add('active');
                });

                document.getElementById('closeInfoPanel').addEventListener('click', function () {
                    document.getElementById('locationInfoPanel').classList.remove('active');
                });
            }
        });

        // Global variable for map
        var map, marker, infowindow;
        // NORSU Dumaguete coordinates
        var mapLocation = { lat: 9.308973, lng: 123.307718 };

        // Document ready function
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize Feather icons
            feather.replace();

            // Initialize the location map if we're on that view
            if (document.getElementById('map')) {
                loadMapScript();

                // Set up event listeners for location panel toggle
                const locationToggle = document.getElementById('locationToggle');
                const locationInfoPanel = document.getElementById('locationInfoPanel');
                const closeInfoPanel = document.getElementById('closeInfoPanel');

                // Toggle panel visibility when clicking the dots
                locationToggle.addEventListener('click', function () {
                    locationInfoPanel.classList.toggle('active');
                });

                // Close panel when clicking X
                closeInfoPanel.addEventListener('click', function () {
                    locationInfoPanel.classList.remove('active');
                });
            }
        });

        // Load the Google Maps API script
        function loadMapScript() {
            // Create script element
            const script = document.createElement('script');
            script.src = 'https://maps.googleapis.com/maps/api/js?key=AIzaSyBbVYILfmKXUzM6m2V3CYC9uRLEYvRWB1U&callback=initMap';
            script.async = true;
            script.defer = true;

            // Add error handling
            script.onerror = function () {
                console.error('Failed to load Google Maps script.');
                document.getElementById('map').innerHTML = '<div class="alert alert-danger m-3">Failed to load Google Maps. Please check your internet connection.</div>';
            };

            // Append script to document
            document.head.appendChild(script);
        }

        // Callback function that will be called by Google Maps API
        function initMap() {
            try {
                // Initialize the map
                map = new google.maps.Map(document.getElementById('map'), {
                    center: mapLocation,
                    zoom: 16,
                    mapTypeControl: true,
                    streetViewControl: true,
                    fullscreenControl: true,
                    zoomControl: true
                });

                // Add a marker
                marker = new google.maps.Marker({
                    position: mapLocation,
                    map: map,
                    title: 'NORSU Dumaguete Campus',
                    animation: google.maps.Animation.DROP
                });

                // Add an info window
                infowindow = new google.maps.InfoWindow({
                    content: '<div style="padding: 5px;"><h5>NORSU Dumaguete Campus</h5><p>Kagawasan Avenue, Dumaguete City</p></div>'
                });

                // Show info window when the map loads
                infowindow.open(map, marker);

                // Toggle info window on marker click
                marker.addListener('click', function () {
                    if (infowindow.getMap()) {
                        infowindow.close();
                    } else {
                        infowindow.open(map, marker);
                    }
                });

                // Center map when window resizes
                google.maps.event.addDomListener(window, 'resize', function () {
                    google.maps.event.trigger(map, 'resize');
                    map.setCenter(mapLocation);
                });
            } catch (e) {
                console.error('Error initializing map:', e);
                document.getElementById('map').innerHTML = '<div class="alert alert-danger m-3">Error initializing Google Maps.</div>';
            }
        }
    </script>
</body>

</html>