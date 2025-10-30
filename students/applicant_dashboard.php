<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit();
}

if ($_SESSION['role'] !== 'student') {
    header('Location: students/login.php');
    exit();
}

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowed_pages = ['dashboard', 'profiling', 'documents', 'notifications', 'support', 'account_settings', 'settings', 'my_account'];

$conn = new mysqli('localhost', 'root', '', 'admission');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM registration WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Dashboard</title>
    <link rel="icon" href="images/chmsu.png" type="image/png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: url('images/chmsubg.jpg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        .overlay {
            background-color: rgba(255, 255, 255, 0.8);
            min-height: 100vh;
            padding-top: 80px;
        }
        .header-bar {
            background-color: rgb(0, 105, 42);
            color: white;
            padding: 1rem;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-left {
            display: flex;
            align-items: center;
        }
        .header-bar img {
            width: 65px;
            margin-right: 10px;
        }
        .sidebar {
            background-color: rgba(232, 245, 233, 0.88);
            position: fixed;
            top: 90px;
            bottom: 0;
            left: 0;
            width: 250px;
            padding: 1rem;
            overflow-y: auto;
        }
        .sidebar a {
            display: block;
            padding: 10px;
            margin-bottom: 5px;
            color: #000;
            text-decoration: none;
            border-radius: 5px;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #c8e6c9;
            font-weight: bold;
        }
        .main-content {
            margin-left: 250px;
            padding: 2rem;
            padding-top: 60px;
        }
        .notification-badge {
            position: absolute;
            top: 2px;
            right: 10px;
            background: red;
            color: white;
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 50%;
        }
        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                width: 90%;
                top: 0;
                margin-bottom: 1rem;
            }
            .main-content {
                margin: auto;
            }
        }
        .dropdown-toggle::after {
            margin-left: 8px;
        }
    </style>
</head>
<body>
<div class="overlay">
    <div class="header-bar">
        <div class="header-left">
            <img src="images/chmsu.png" alt="CHMSU Logo">
            <div class="ms-1">
                <h4 class="mb-0">Carlos Hilado Memorial State University</h4>
                <p class="mb-0">Academic Program Application and Screening Management System</p>
            </div>
        </div>
        <div class="me-3">
        <a class="dropdown-item" href="?page=my_account"><i class="fas fa-user"></i> <?= htmlspecialchars(ucwords(strtolower(($user['first_name'] ?? '') . ' ' . ($user['middle_name'] ?? '') . ' ' . ($user['last_name'] ?? '')))) ?></a></li>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 sidebar">
                <a href="?page=dashboard" class="<?= $page === 'dashboard' ? 'active' : '' ?>"><i class="fas fa-home"></i> Home</a>
                <a href="?page=profiling" class="<?= $page === 'profiling' ? 'active' : '' ?>"><i class="fas fa-user"></i> Applicant Profiling</a>
                <a href="?page=account_settings" class="<?= $page === 'account_settings' ? 'active' : '' ?>"><i class="fas fa-user-cog"></i> Password Setting</a>
                <a href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>

            <div class="col-md-9 main-content">
                <?php
                if (in_array($page, $allowed_pages) && file_exists("$page.php")) {
                    include("$page.php");
                } else {
                    echo "<div class='alert alert-danger'>Page not found or under development.</div>";
                }
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #00692a; color: white;">
        <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to logout?</p>
        <p class="text-muted">You will need to login again to access the system.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <a href="logout.php" class="btn" style="background-color: #00692a; color: white; border: 1px solid #00692a;">Logout</a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
