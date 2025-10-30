<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require __DIR__ . '/../vendor/autoload.php';

$success_message = '';
$error_message = '';

// Show messages from session if redirected
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'admission');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capture and sanitize inputs
    $last_name = trim($_POST['last_name']);
    $first_name = trim($_POST['first_name']);
    $email = trim($_POST['email_address']);
    $app_status = trim($_POST['applicant_status']); // From radio buttons

    // Validate required fields
    if (empty($last_name) || empty($first_name) || empty($email) || empty($app_status)) {
        $_SESSION['error_message'] = "All fields are required.";
        header("Location: register.php");
        exit();
    } else {
        // Set session
        $_SESSION['applicant_status'] = $app_status;

        // Generate password
        $password = substr(str_shuffle('abcdefghijkmnopqrstuvwxyz0123456789'), 0, 6);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check for duplicate email
        $check_sql = "SELECT id FROM registration WHERE email_address = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['error_message'] = "Email address already registered!";
            header("Location: register.php");
            exit();
        } else {
            // Insert new record
            $sql = "INSERT INTO registration (email_address, password, first_name, last_name, applicant_status) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $email, $hashed_password, $first_name, $last_name, $app_status);

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $stmt->insert_id;

                // Send email
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'acregalado.chmsu@gmail.com';
                    $mail->Password   = 'vvekpeviojyyysfq';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('acregalado.chmsu@gmail.com', 'CCS Admission Committee');
                    $mail->addAddress($email, "$first_name $last_name");

                    $mail->isHTML(false);
                    $mail->Subject = "Your CHMSU Application Account Credentials";
                    $mail->Body = "Dear $first_name $last_name,\n\n"
                                . "Thank you for registering with CHMSU. Here are your login credentials:\n\n"
                                . "Email: $email\n"
                                . "Password: $password\n\n"
                                . "Please login at: http://localhost/CAPSTONE/students/login.php\n\n"
                                . "Best regards,\nCCS Admission Committee";

                    $mail->send();
                    $_SESSION['success_message'] = "Registration successful! Please check your email for login credentials.";
                } catch (Exception $e) {
                    $_SESSION['success_message'] = "Registration successful, but email could not be sent. Error: {$mail->ErrorInfo}";
                }
                header("Location: login.php");
                exit();
            } else {
                $_SESSION['error_message'] = "Registration failed. Please try again.";
                header("Location: register.php");
                exit();
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
  <title>Carlos Hilado Memorial State University</title>
  <link rel="icon" href="images/chmsu.png" type="image/png" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: url('images/chmsubg.jpg') no-repeat center center fixed;
      background-size: cover;
    }
    .overlay {
      background-color: rgba(255, 255, 255, 0.3);
      min-height: 100vh;
    }
    .left-panel {
      background-color: rgba(232, 245, 233, 0.88);
      padding: 2rem;
      min-height: 85vh;
    }
    .right-panel {
      padding: 2rem;
    }

    .header-bar {
      background-color:rgb(0, 105, 42);
      color: white;
      padding: 1rem;
    }
    
    /* Custom button styles to match header theme */
    .btn[style*="rgb(0, 105, 42)"] {
      transition: all 0.3s ease;
    }
    
    .btn[style*="rgb(0, 105, 42)"]:hover {
      background-color: rgb(0, 82, 35) !important;
      transform: translateY(-1px);
      box-shadow: 0 4px 8px rgba(0, 105, 42, 0.3);
    }
    
    .btn[style*="rgb(0, 105, 42)"]:active {
      transform: translateY(0);
    }
    
    /* Fix form input focus states to match green theme */
    .form-control:focus,
    .form-check-input:focus {
      border-color: rgb(0, 105, 42) !important;
      box-shadow: 0 0 0 0.2rem rgba(0, 105, 42, 0.25) !important;
    }
    
    .form-check-input:checked {
      background-color: rgb(0, 105, 42) !important;
      border-color: rgb(0, 105, 42) !important;
    }
    
    .form-check-input:focus {
      border-color: rgb(0, 105, 42) !important;
      box-shadow: 0 0 0 0.2rem rgba(0, 105, 42, 0.25) !important;
    }
    .header-bar img {
      width: 65px;
      margin-right: 10px;
    }
    .modal-body {
      font-size: 0.95rem;
    }
    .modal-title{
        text-align: center;
    }
    .modal-header img {
      width: 50px;
      align-items: center;
    }
     input.uppercase {
            text-transform: uppercase;
        }
  </style>
</head>
<body>
<div class="overlay">
  <!-- Header -->
  <div class="header-bar d-flex align-items-center">
    <img src="images/chmsu.png" alt="CHMSU Logo">
    <div class="ms-1">
      <h4 class="mb-0">Carlos Hilado Memorial State University </h4>
      <p class="mb-0">Academic Program Application and Screening Management System</p>
    </div>
  </div>

  <div class="container-fluid">
    <div class="row">
      <!-- Left Panel -->
      <div class="col-md-6 left-panel">
        <h5>Welcome Applicant!</h5>
        <p>Please make sure you have your personal email ready. Your login credentials will be sent there after registration.</p>
        <ul>
          <li>Applicants must have passed the CHMSU Entrance Examination.</li>
          <li>If you are a shiftee or an old student that is enrolled or was enrolled in CHMSU, <b> DO NOT USE THIS SYSTEM</b>.</li>
          <li>Use only one email address in the application. We prohibit the applicant to use multiple email addresses to create multiple account with the same name. Once traced, only the first entry will be acknowledge and the rest will be disregarded. </li>
          <li>Applicants are prohibited from sharing the same email address to other applicants. Use your own email address in applying.</li>
          <li>Input all your information with honesty and integrity. The data encoded and submitted documents will be subjected to verification and validation.</li>
          <li>Prepare your requirements before proceeding to the full application.</li>
          <li>Only one application per department is allowed.</li>
          <li>Applicants who will violate the aforementioned guidelines will be disqualified from the list of application.</li>
        </ul>
      </div>

      <!-- Right Panel: Registration Form -->
      <div class="col-md-6 right-panel">
        <div class="card shadow">
          <div class="card-body">
            <?php if (!empty($success_message)): ?>
  <div class="alert alert-success"><?php echo $success_message; ?></div>
<?php elseif (!empty($error_message)): ?>
  <div class="alert alert-danger"><?php echo $error_message; ?></div>
<?php endif; ?>

            <h5 class="card-title mb-2">Register</h5>
            <form id="registrationForm" method="POST" action="">

              <div class="mb-2">
                <label for="lastName" class="form-label">Last Name</label>
                <input type="text" class="form-control uppercase" id="lastName" name="last_name" required>
              </div>
              <div class="mb-2">
                <label for="firstName" class="form-label">First Name</label>
                <input type="text" class="form-control uppercase" id="firstName" name="first_name" required>
              </div>
              <div class="mb-2">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email_address" required>
              </div>

              <div class="mb-2">
                <label class="form-label">Application Status</label><br>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="applicant_status" value="Transferee" required>
                  <label class="form-check-label">Transferee</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="applicant_status" id="new_same_year" value="New Applicant - Same Academic Year" required>
                  <label class="form-check-label">New Applicant (same academic year)</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="applicant_status" value="New Applicant - Previous Academic Year" required>
                  <label class="form-check-label">New Applicant (previous academic year)</label>
                </div>
              </div>

              <button type="submit" class="btn w-100" style="background-color: rgb(0, 105, 42); color: white; border: none;">Register</button>
              <div class="mt-2 text-center">
  <p>Already have an account? <a href="login.php" class="text-success">Login here</a></p>
</div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal -->

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    <?php if (empty($success_message) && empty($error_message)): ?>
      const usageModal = new bootstrap.Modal(document.getElementById('usageModal'));
      window.addEventListener('load', () => {
        usageModal.show();
      });
    <?php endif; ?>
  </script> 
</body>
</html>