<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'admission');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Generate form token if not exists
if (!isset($_SESSION['password_change_token'])) {
    $_SESSION['password_change_token'] = bin2hex(random_bytes(32));
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    // Check for duplicate submission using session
    if (isset($_SESSION['last_password_change']) && 
        (time() - $_SESSION['last_password_change']) < 5) {
        $error_message = "Please wait before submitting again.";
    } elseif (!isset($_POST['form_token']) || $_POST['form_token'] !== $_SESSION['password_change_token']) {
        $error_message = "Invalid form submission. Please try again.";
    } else {
        $_SESSION['last_password_change'] = time();
        // Regenerate token after successful submission
        $_SESSION['password_change_token'] = bin2hex(random_bytes(32));
        
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate inputs
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = "All fields are required.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New password and confirm password do not match.";
        } elseif (strlen($new_password) < 6) {
            $error_message = "New password must be at least 6 characters long.";
        } else {
        // Get current password from database
        $interviewer_id = $_SESSION['interviewer_id'];
        $stmt = $conn->prepare("SELECT password FROM interviewers WHERE id = ?");
        
        if (!$stmt) {
            $error_message = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("i", $interviewer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        }
        
        if ($stmt && $user && password_verify($current_password, $user['password'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE interviewers SET password = ? WHERE id = ?");
            
            if (!$update_stmt) {
                $error_message = "Database error: " . $conn->error;
            } else {
                $update_stmt->bind_param("si", $hashed_password, $interviewer_id);
                
                if ($update_stmt->execute()) {
                    // Store success message in session and redirect
                    $_SESSION['success_message'] = "Password changed successfully!";
                    header("Location: ?page=interviewer_dashboard");
                    exit;
                } else {
                    $error_message = "Error updating password. Please try again.";
                }
                $update_stmt->close();
            }
        } elseif ($stmt && $user) {
            $error_message = "Current password is incorrect.";
        } elseif ($stmt) {
            $error_message = "User not found. Please contact administrator.";
        }
        
        if ($stmt) {
            $stmt->close();
        }
        }
    }
}

// Only handle error messages on this page (success redirects to dashboard)
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>

<style>
    .password-card {
        max-width: 600px;
        margin: 0 auto;
    }
    
    .form-control:focus {
        border-color: rgb(0, 105, 42);
        box-shadow: 0 0 0 0.2rem rgba(0, 105, 42, 0.25);
    }
    
    .btn-primary {
        background-color: rgb(0, 105, 42);
        border-color: rgb(0, 105, 42);
    }
    
    .btn-primary:hover {
        background-color: rgb(0, 85, 34);
        border-color: rgb(0, 85, 34);
    }
    
    .password-strength {
        height: 4px;
        background-color: #e9ecef;
        border-radius: 2px;
        margin-top: 5px;
        overflow: hidden;
    }
    
    .password-strength-bar {
        height: 100%;
        transition: all 0.3s ease;
        border-radius: 2px;
    }
    
    .strength-weak { background-color: #dc3545; width: 25%; }
    .strength-fair { background-color: #ffc107; width: 50%; }
    .strength-good { background-color: #17a2b8; width: 75%; }
    .strength-strong { background-color: #28a745; width: 100%; }
    
    /* Toast styling */
    .toast {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        border-radius: 0.5rem;
    }
    
    .toast-body {
        font-weight: 500;
    }
</style>

<div class="container-fluid px-4" style="padding-top: 30px;">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-0"><i class="fas fa-key me-2"></i>Change Password</h4>
        </div>
    </div>

    <!-- Toast Container -->
    <?php if (isset($error_message)): ?>
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1060;">
        <div id="errorToast" class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error_message) ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Password Change Form -->
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card password-card shadow-sm">
                <div class="card-header" style="background-color: rgb(0, 105, 42); color: white;">
                    <h5 class="mb-0">
                        <i class="fas fa-lock me-2"></i>Change Your Password
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> Please change your password to a secure one for better account security.
                    </div>
                    
                    <form id="changePasswordForm" method="POST">
                        <input type="hidden" name="form_token" value="<?= $_SESSION['password_change_token'] ?>">
                        <div class="mb-4">
                            <label for="currentPassword" class="form-label">
                                <i class="fas fa-key me-1"></i>Current Password
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('currentPassword')">
                                    <i class="fas fa-eye" id="currentPasswordIcon"></i>
                                </button>
                            </div>
                            <div class="form-text">Enter your current password.</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="newPassword" class="form-label">
                                <i class="fas fa-lock me-1"></i>New Password
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="newPassword" name="new_password" required minlength="6">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('newPassword')">
                                    <i class="fas fa-eye" id="newPasswordIcon"></i>
                                </button>
                            </div>
                            <div class="password-strength">
                                <div class="password-strength-bar" id="strengthBar"></div>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-shield-alt me-1"></i>
                                Password must be at least 6 characters long. Use a combination of letters, numbers, and symbols for better security.
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirmPassword" class="form-label">
                                <i class="fas fa-lock me-1"></i>Confirm New Password
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirmPassword')">
                                    <i class="fas fa-eye" id="confirmPasswordIcon"></i>
                                </button>
                            </div>
                            <div class="form-text">Re-enter your new password to confirm.</div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                <i class="fas fa-save me-2"></i>Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Confirmation Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
                <h5 class="modal-title" id="changePasswordModalLabel">
                    <i class="fas fa-key me-2"></i>Confirm Password Change
                </h5>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to change your password?</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Note:</strong> You will need to use your new password for future logins.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn" style="background-color: rgb(0, 105, 42); color: white; border: 1px solid rgb(0, 105, 42);" onclick="confirmPasswordChange()">
                    <i class="fas fa-save me-2"></i>Change Password
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-show error toast if it exists
document.addEventListener('DOMContentLoaded', function() {
    const errorToast = document.getElementById('errorToast');
    
    if (errorToast) {
        const toast = new bootstrap.Toast(errorToast, {
            autohide: true,
            delay: 5000
        });
        toast.show();
        
        // Remove toast from DOM after it's hidden
        errorToast.addEventListener('hidden.bs.toast', function() {
            errorToast.remove();
        });
    }
});

// Password strength indicator
document.getElementById('newPassword').addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('strengthBar');
    
    if (password.length === 0) {
        strengthBar.className = 'password-strength-bar';
        strengthBar.style.width = '0%';
    } else if (password.length < 6) {
        strengthBar.className = 'password-strength-bar strength-weak';
    } else if (password.length < 8) {
        strengthBar.className = 'password-strength-bar strength-fair';
    } else if (password.length < 12) {
        strengthBar.className = 'password-strength-bar strength-good';
    } else {
        strengthBar.className = 'password-strength-bar strength-strong';
    }
});

// Form validation before showing modal
document.querySelector('button[data-bs-target="#changePasswordModal"]').addEventListener('click', function(e) {
    e.preventDefault();
    
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    // Clear previous validation
    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
    
    let isValid = true;
    
    // Validate current password
    if (!currentPassword) {
        showFieldError('currentPassword', 'Current password is required.');
        isValid = false;
    }
    
    // Validate new password
    if (!newPassword) {
        showFieldError('newPassword', 'New password is required.');
        isValid = false;
    } else if (newPassword.length < 6) {
        showFieldError('newPassword', 'Password must be at least 6 characters long.');
        isValid = false;
    }
    
    // Validate confirm password
    if (!confirmPassword) {
        showFieldError('confirmPassword', 'Please confirm your new password.');
        isValid = false;
    } else if (newPassword !== confirmPassword) {
        showFieldError('confirmPassword', 'Passwords do not match.');
        isValid = false;
    }
    
    if (isValid) {
        // Show confirmation modal
        const modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
        modal.show();
    }
});

// Confirm password change
function confirmPasswordChange() {
    // Prevent double submission
    const submitButton = document.querySelector('button[data-bs-target="#changePasswordModal"]');
    const confirmButton = document.querySelector('button[onclick="confirmPasswordChange()"]');
    
    // Disable buttons to prevent double submission
    submitButton.disabled = true;
    confirmButton.disabled = true;
    
    // Add loading state
    confirmButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Changing Password...';
    
    // Add hidden input to indicate password change
    const form = document.getElementById('changePasswordForm');
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'change_password';
    hiddenInput.value = '1';
    form.appendChild(hiddenInput);
    
    // Submit form
    form.submit();
}

// Helper function to show field errors
function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    field.classList.add('is-invalid');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
}

// Toggle password visibility
function togglePassword(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + 'Icon');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>
