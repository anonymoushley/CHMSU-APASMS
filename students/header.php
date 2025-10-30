<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHMSU Scholarship Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .navbar {
            background-color: #00692a;
        }
        .navbar-brand img {
            width: 40px;
            margin-right: 10px;
        }
        .nav-link {
            color: white !important;
        }
        .nav-link:hover {
            color: #e0e0e0 !important;
        }
        .dropdown-menu {
            background-color: #00692a;
        }
        .dropdown-item {
            color: white !important;
        }
        .dropdown-item:hover {
            background-color: #005223;
            color: white !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="applicant_dashboard.php">
                <img src="../assets/images/chmsu-logo.png" alt="CHMSU Logo">
                CHMSU Scholarship Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="applicant_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">My Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="exam.php">Take Exam</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php 
                                $name = $_SESSION['name'] ?? 'User';
                                // Split name into parts and capitalize each part
                                $nameParts = explode(' ', trim($name));
                                $capitalizedParts = array();
                                foreach($nameParts as $part) {
                                    $capitalizedParts[] = ucfirst(strtolower(trim($part)));
                                }
                                $name = implode(' ', $capitalizedParts);
                                echo htmlspecialchars($name); 
                            ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="account_settings.php">Account Settings</a></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4"> 