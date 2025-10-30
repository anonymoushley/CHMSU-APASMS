<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli('localhost', 'root', '', 'admission');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Use session flash messages for PRG + toasts
if (!isset($_SESSION['success_message'])) {
    $_SESSION['success_message'] = '';
}
if (!isset($_SESSION['error_message'])) {
    $_SESSION['error_message'] = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        $_SESSION['error_message'] = "All fields are required.";
    } elseif ($new_pass !== $confirm_pass) {
        $_SESSION['error_message'] = "New password and confirm password do not match.";
    } else {
        $chair_id = $_SESSION['chair_id'];

        $stmt = $conn->prepare("SELECT password FROM chairperson_accounts WHERE id = ?");
        $stmt->bind_param("i", $chair_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($current_pass, $user['password'])) {
            $new_hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE chairperson_accounts SET password = ? WHERE id = ?");
            $update->bind_param("si", $new_hashed, $chair_id);
            $update->execute();
            $_SESSION['success_message'] = "Password changed successfully.";
        } else {
            $_SESSION['error_message'] = "Current password is incorrect.";
        }
    }
    // Redirect to avoid form resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . "?page=chair_changepass");
    exit;
}
?>

<style>
    .show-toggle {
        cursor: pointer;
        user-select: none;
        font-size: 0.9em;
        color: rgb(0, 105, 42);
    }
    .btn-header-theme {
        background-color: rgb(0, 105, 42);
        color: white;
        border: 1px solid rgb(0, 105, 42);
        transition: all 0.2s ease;
    }
    .btn-header-theme:hover {
        background-color: rgb(0, 85, 34);
        border-color: rgb(0, 85, 34);
        color: white;
    }
    .btn-header-theme:active {
        background-color: rgb(0, 65, 26);
        border-color: rgb(0, 65, 26);
        color: white;
    }
    
    .input-group .btn-outline-secondary {
        border-color: #ced4da;
        color: #6c757d;
    }
    
    .input-group .btn-outline-secondary:hover {
        background-color: #e9ecef;
        border-color: #ced4da;
        color: #495057;
    }
</style>

<div class="container-fluid px-4 py-3">
    <!-- Page Title -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-0" style="margin-top: 10px;"><i class="fas fa-user-cog me-2"></i>Change Password</h4>
        </div>
    </div>
        
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-lg">
                    <div class="card-header text-white" style="background-color: rgb(0, 105, 42);">
                        <h5 class="mb-0"><i class="fas fa-key"></i> Update Your Password</h5>
                    </div>
            <div class="card-body">
                <?php if (!empty($_SESSION['success_message'])): ?>
                    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1060;">
                        <div id="successToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
                            <div class="d-flex">
                                <div class="toast-body">
                                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['success_message']) ?>
                                </div>
                                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                            </div>
                        </div>
                    </div>
                    <?php $_SESSION['success_message'] = ''; ?>
                <?php endif; ?>

                <?php if (!empty($_SESSION['error_message'])): ?>
                    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1060;">
                        <div id="errorToast" class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
                            <div class="d-flex">
                                <div class="toast-body">
                                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['error_message']) ?>
                                </div>
                                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                            </div>
                        </div>
                    </div>
                    <?php $_SESSION['error_message'] = ''; ?>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <div class="input-group">
                            <input type="password" name="current_password" id="current_password" class="form-control" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                <i class="fas fa-eye" id="current_password_icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="new_password" class="form-control" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                <i class="fas fa-eye" id="new_password_icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye" id="confirm_password_icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-header-theme">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePassword(fieldId) {
        const input = document.getElementById(fieldId);
        const icon = document.getElementById(fieldId + '_icon');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Show toast notifications on load
    document.addEventListener('DOMContentLoaded', function() {
        const successToast = document.getElementById('successToast');
        const errorToast = document.getElementById('errorToast');
        if (successToast) {
            const t = new bootstrap.Toast(successToast);
            t.show();
        }
        if (errorToast) {
            const t2 = new bootstrap.Toast(errorToast);
            t2.show();
        }
    });
</script>

