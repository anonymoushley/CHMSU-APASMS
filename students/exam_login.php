<?php
session_start();

// Database connection
$conn = new mysqli('localhost', 'root', '', 'admission');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';
if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email_address'];
    $password = $_POST['password'];
    
    // Check if it's admin login
    if ($email === 'admin@chmsu.edu.ph') {
        $sql = "SELECT * FROM registration WHERE email_address = ? AND role = 'admin'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email_address'];
                $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['role'] = 'admin';
                header('Location: ../admin/dashboard.php');
                exit();
            } else {
                $_SESSION['login_error'] = "Invalid admin credentials!";
                header("Location: exam_login.php");
                exit();
            }
        } else {
            $_SESSION['login_error'] = "Invalid admin credentials!";
            header("Location: exam_login.php");
            exit();
        }
    } else {
        // Student login
        $sql = "SELECT * FROM registration WHERE email_address = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email_address'];
                $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['role'] = 'student';
                header('Location: exam.php');
                exit();
            } else {
                $_SESSION['login_error'] = "Invalid password!";
                header("Location: exam_login.php");
                exit();
            }
        } else {
            $_SESSION['login_error'] = "No account found with that email address!";
            header("Location: exam_login.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CHMSU</title>
    <link rel="icon" href="images/chmsu.png" type="image/png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: url('images/chmsubg.jpg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            margin:auto;
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo img {
            width: 100px;
            height: auto;
        }
        .header-text {
            text-align: center;
            margin-bottom: 30px;
        }
        .header-text h4 {
            color: #00692a;
            margin-bottom: 5px;
        }
        .header-text p {
            color: #666;
            font-size: 0.9em;
        }
        .form-control {
            border-radius: 5px;
            padding: 10px 15px;
        }
        .form-control:focus {
            border-color: #00692a;
            box-shadow: 0 0 0 0.2rem rgba(0, 105, 42, 0.25);
        }
        .btn-primary {
            background-color: #00692a;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
        }
        .btn-primary:hover {
            background-color: #005223;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        .admin-note {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 0.9em;
        }
       .overlay {
            background-color: rgba(255, 255, 255, 0.5);
            min-height: 100vh;
            min-width: 100vw;
            padding-top: 40px;
        }
        
        /* Toast Styles - Matching Exam Login Theme */
        .toast {
            background-color: #dc3545;
            border: 1px solid #c82333;
            color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
            min-width: 300px;
        }
        
        .toast-header {
            background-color: #dc3545;
            border-bottom: 1px solid #c82333;
            color: #ffffff;
            font-weight: 600;
        }
        
        .toast-body {
            padding: 12px 16px;
            color: #ffffff;
        }
        
        .toast .btn-close {
            filter: invert(1);
        }
        
        .toast .btn-close:hover {
            filter: invert(1) brightness(0.8);
        }
    </style>
</head>

<body>
    <div class="overlay">
    <div class="login-container">
        <div class="logo">
            <img src="images/chmsu.png" alt="CHMSU Logo">
        </div>
        <div class="header-text">
            <h4>Carlos Hilado Memorial State University</h4>
            <p>College of Computer Studies Exam Portal</p>
        </div>
        
        <!-- Toast Container -->
        <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;"></div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger" id="errorAlert" style="display: none;"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email_address" class="form-control" required>
            </div>
           <div class="mb-3">
    <label class="form-label">Password</label>
    <div class="input-group">
        <input type="password" name="password" class="form-control" id="passwordInput" required>
        <span class="input-group-text" id="togglePassword" style="cursor:pointer;">
            <i class="fa fa-eye" id="eyeIcon"></i>
        </span>
    </div>
</div>
            <div class="text-center">
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </div>
        </form>
        

    </div>

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <script>
    const passwordInput = document.getElementById('passwordInput');
    const togglePassword = document.getElementById('togglePassword');
    const eyeIcon = document.getElementById('eyeIcon');

    togglePassword.addEventListener('click', function () {
        const type = passwordInput.type === 'password' ? 'text' : 'password';
        passwordInput.type = type;
        eyeIcon.classList.toggle('fa-eye');
        eyeIcon.classList.toggle('fa-eye-slash');
    });

    // Show error toast if there's an error message
    <?php if ($error): ?>
    showErrorToast('<?php echo addslashes($error); ?>');
    <?php endif; ?>
    
    function showErrorToast(message) {
        const toastContainer = document.getElementById('toastContainer');
        const toastId = 'toast-' + Date.now();
        
        const toastHTML = `
            <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <i class="fas fa-exclamation-circle text-white me-2"></i>
                    <strong class="me-auto">Error</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;
        
        toastContainer.innerHTML = toastHTML;
        
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 5000
        });
        
        toast.show();
    }
</script>
</body>
</html> 