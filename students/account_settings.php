<?php 
require_once '../config/database.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

// No server-side form handling needed - using AJAX
?>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .settings-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            width: 100%;
            margin: auto;
        }
        .settings-header {
            background-color: #00692a;
            color: white;
            padding: 15px;
            border-radius: 8px 8px 0 0;
        }
        .settings-body {
            padding: 20px;
        }
        .btn-header-theme {
            background-color: #00692a !important;
            color: white !important;
            border: 1px solid #00692a !important;
            transition: all 0.2s ease;
        }
        .btn-header-theme:hover {
            background-color: #005223 !important;
            border-color: #005223 !important;
            color: white !important;
        }
        .btn-header-theme:active {
            background-color: #004a1f !important;
            border-color: #004a1f !important;
            color: white !important;
        }
        .btn-header-theme:focus {
            background-color: #00692a !important;
            border-color: #00692a !important;
            color: white !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 105, 42, 0.25) !important;
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
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col-md-6 justify-content-center mx-auto">
            <!-- Toast Container -->
            <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;"></div>

            <div class="settings-card">
                <div class="settings-header">
                    <h5 class="mb-0"><i class="fas fa-key"></i> Update Your Password</h5>
                </div>
                <div class="settings-body">
                    <form id="passwordForm" method="POST" action="">
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
                            <div class="form-text">At least 8 characters long.</div>
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
                            <button type="submit" name="change_password" class="btn btn-header-theme">Change Password</button>
                        </div>
                    </form>
                </div>
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

// Toast Functions
function showSuccessToast(message) {
    const toastContainer = document.getElementById('toastContainer');
    const toastId = 'toast-' + Date.now();
    
    const toastHTML = `
        <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header" style="background-color: #00692a; color: white;">
                <i class="fas fa-check-circle text-white me-2"></i>
                <strong class="me-auto">Success</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" style="background-color: #00692a; color: white;">
                ${message}
            </div>
        </div>
    `;
    
    toastContainer.innerHTML = toastHTML;
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 2000
    });
    
    toast.show();
}

function showErrorToast(message) {
    const toastContainer = document.getElementById('toastContainer');
    const toastId = 'toast-' + Date.now();
    
    const toastHTML = `
        <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header" style="background-color: #dc3545; color: white;">
                <i class="fas fa-exclamation-circle text-white me-2"></i>
                <strong class="me-auto">Error</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" style="background-color: #dc3545; color: white;">
                ${message}
            </div>
        </div>
    `;
    
    toastContainer.innerHTML = toastHTML;
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 2000
    });
    
    toast.show();
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // No initial messages to show - using AJAX for real-time feedback
});

// AJAX Form Submission
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    // Add the change_password field that the PHP script expects
    formData.append('change_password', '1');
    
    // Debug: Log form data
    console.log('Form data being sent:');
    for (let [key, value] of formData.entries()) {
        console.log(key, value);
    }
    
    fetch('change_password.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessToast(data.message);
            // Clear form after successful password change
            document.getElementById('passwordForm').reset();
        } else {
            showErrorToast(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorToast('An error occurred while updating your password.');
    });
});
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
