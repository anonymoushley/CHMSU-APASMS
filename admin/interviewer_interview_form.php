<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in as interviewer
if (!isset($_SESSION['interviewer_id']) || $_SESSION['user_type'] !== 'interviewer') {
    header("Location: chair_login.php");
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'admission');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$applicant_id = $_GET['applicant_id'] ?? null;
$applicant_data = null;

// Get applicant data if ID is provided
if ($applicant_id) {
    $sql = "SELECT 
                r.id AS registration_id,
                r.applicant_status,
                pi.last_name,
                pi.first_name,
                pi.middle_name,
                pi.contact_number,
                s.name as strand,
                ab.year_graduated,
                pa.program,
                pa.campus,
                sr.interview_total_score
            FROM registration r
            LEFT JOIN personal_info pi ON r.personal_info_id = pi.id
            LEFT JOIN academic_background ab ON ab.personal_info_id = pi.id
            LEFT JOIN strands s ON ab.strand_id = s.id
            LEFT JOIN program_application pa ON pa.personal_info_id = pi.id
            LEFT JOIN screening_results sr ON sr.personal_info_id = pi.id
            WHERE r.id = ? AND r.applicant_status = 'For Interview'";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $applicant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $applicant_data = $result->fetch_assoc();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_interview'])) {
    // Calculate scores from radio buttons
    $section1_total = 0;
    $section2_total = 0;
    $section3_total = 0;
    $writing_score = 0;
    $reading_score = intval($_POST['reading_score']);
    
    // Section 1: Preparedness (4 items, max 5 each = 20 points)
    for ($i = 0; $i < 4; $i++) {
        $section1_total += intval($_POST["section1_item$i"] ?? 0);
    }
    
    // Section 2: Communication Skills (4 items, max 5 each = 20 points)
    for ($i = 0; $i < 4; $i++) {
        $section2_total += intval($_POST["section2_item$i"] ?? 0);
    }
    
    // Section 3: Personal/Physical/Social Traits (4 items, max 5 each = 20 points)
    for ($i = 0; $i < 4; $i++) {
        $section3_total += intval($_POST["section3_item$i"] ?? 0);
    }
    
    // Writing Skills (1 item, max 5 = 5 points, but multiplied by 4 to get 20 points)
    $writing_score = intval($_POST['writing'] ?? 0) * 4;
    
    // Total Score (out of 100)
    $total_score = $section1_total + $section2_total + $section3_total + $writing_score + $reading_score;
    
    // Final Interview Score (out of 50)
    $final_score = ($total_score / 100) * 50;
    
    // Get personal_info_id from registration
    $reg_sql = "SELECT personal_info_id FROM registration WHERE id = ?";
    $reg_stmt = $conn->prepare($reg_sql);
    $reg_stmt->bind_param("i", $applicant_id);
    $reg_stmt->execute();
    $reg_result = $reg_stmt->get_result();
    $reg_data = $reg_result->fetch_assoc();
    
    if ($reg_data) {
        $personal_info_id = $reg_data['personal_info_id'];
        
        // Update or insert interview results
        $update_sql = "INSERT INTO screening_results (personal_info_id, interview_total_score)
                       VALUES (?, ?)
                       ON DUPLICATE KEY UPDATE 
                       interview_total_score = VALUES(interview_total_score)";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("id", $personal_info_id, $final_score);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Interview evaluation submitted successfully!";
            // Redirect to prevent form resubmission
            header("Location: interviewer_main.php?page=interviewer_applicants");
            exit;
        } else {
            $_SESSION['error_message'] = "Error saving interview evaluation.";
        }
    } else {
        $_SESSION['error_message'] = "Applicant not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Interview Form - Interviewer Portal</title>
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
      background-color: rgba(255, 255, 255, 0.85);
      min-height: 100vh;
      padding-top: 120px;
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
    }
    .header-bar img {
      width: 65px;
      margin-right: 10px;
    }
    .interview-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-left: 5px solid rgb(0, 105, 42);
    }
    
    .applicant-info {
        background: linear-gradient(135deg, rgb(0, 105, 42), rgb(0, 85, 34));
        color: white;
        padding: 20px;
        border-radius: 10px 10px 0 0;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        font-weight: 600;
        color: #333;
    }
    
    .score-input {
        width: 100px;
        text-align: center;
    }
    
    .btn-save {
        background-color: rgb(0, 105, 42);
        color: white;
        border: 1px solid rgb(0, 105, 42);
        transition: all 0.2s ease;
    }
    
    .btn-save:hover {
        background-color: rgb(0, 85, 34);
        border-color: rgb(0, 85, 34);
        color: white;
    }
    
    .btn-back {
        background-color: rgb(0, 105, 42);
        color: white;
        border: 1px solid rgb(0, 105, 42);
        transition: all 0.2s ease;
    }
    
    .btn-back:hover {
        background-color: rgb(0, 85, 34);
        border-color: rgb(0, 85, 34);
        color: white;
    }
    
    .form-check-input:checked {
        background-color: rgb(0, 105, 42);
        border-color: rgb(0, 105, 42);
    }
    
    .form-check-input:focus {
        border-color: rgb(0, 105, 42);
        box-shadow: 0 0 0 0.25rem rgba(0, 105, 42, 0.25);
    }
    
    .btn-confirm:hover {
        background-color: rgb(0, 85, 34) !important;
        border-color: rgb(0, 85, 34) !important;
        color: white !important;
    }
    
    .score-display {
        font-size: 1.2em;
        font-weight: bold;
        color: rgb(0, 105, 42);
    }
  </style>
</head>
<body>
<div class="overlay">
  <div class="header-bar d-flex align-items-center">
    <img src="images/chmsu.png" alt="CHMSU Logo">
    <div class="ms-1">
      <h4 class="mb-0">Carlos Hilado Memorial State University</h4>
      <p class="mb-0">Interviewer Portal - Interview Form</p>
    </div>
  </div>

  <!-- Toast Container -->
  <?php 
    // Store messages in variables and clear session immediately
    $success_message = $_SESSION['success_message'] ?? null;
    $error_message = $_SESSION['error_message'] ?? null;
    unset($_SESSION['success_message']);
    unset($_SESSION['error_message']);
    
    if ($success_message || $error_message):
  ?>
  <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1060;">
    <?php if ($success_message): ?>
      <div id="successToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
        <div class="d-flex">
          <div class="toast-body"><?= htmlspecialchars($success_message) ?></div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
      <div id="errorToast" class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
        <div class="d-flex">
          <div class="toast-body"><?= htmlspecialchars($error_message) ?></div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Confirmation Modal -->
  <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header" style="background-color: rgb(0, 105, 42); color: white;">
          <h5 class="modal-title" id="confirmationModalLabel">
            <i class="fas fa-question-circle me-2"></i>Confirm Interview Evaluation
          </h5>
        </div>
        <div class="modal-body">
          <div class="text-center mb-3">
            <i class="fas fa-clipboard-check fa-3x text-success mb-3"></i>
            <h6>Please review your evaluation scores:</h6>
          </div>
          <div class="row">
            <div class="col-6">
              <div class="card border-success">
                <div class="card-body text-center">
                  <h6 class="card-title text-success">Total Score</h6>
                  <h4 class="text-success" id="modalTotalScore">0</h4>
                  <small class="text-muted">out of 100</small>
                </div>
              </div>
            </div>
            <div class="col-6">
              <div class="card border-success">
                <div class="card-body text-center">
                  <h6 class="card-title text-success">Interview Score</h6>
                  <h4 class="text-success" id="modalFinalScore">0</h4>
                  <small class="text-muted">out of 50</small>
                </div>
              </div>
            </div>
          </div>
          <div class="alert alert-info mt-3">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Note:</strong> Once submitted, this evaluation cannot be modified.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-2"></i>Cancel
          </button>
          <button type="button" class="btn btn-confirm" style="background-color: rgb(0, 105, 42); color: white; border: 1px solid rgb(0, 105, 42);" id="confirmSubmitBtn">
            <i class="fas fa-check me-2"></i>Confirm & Submit
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="container-fluid px-4 py-3">
    <!-- Header with Back Button -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <a href="interviewer_main.php?page=interviewer_applicants" class="btn btn-back me-3">
                        <i class="fas fa-arrow-left me-1"></i> Back to Applicants
                    </a>
                    <h4 class="mb-0"><i class="fas fa-microphone me-2"></i>Interview Form</h4>
                </div>
            </div>
        </div>
    </div>

    <?php if ($applicant_data): ?>
        <div class="row">
            <div class="col-12">
                <div class="interview-card">
                    <!-- Applicant Information -->
                    <div class="applicant-info">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="mb-2">
                                    <i class="fas fa-user me-2"></i>
                                    <?= htmlspecialchars(ucwords(strtolower($applicant_data['first_name'] . ' ' . $applicant_data['middle_name'] . ' ' . $applicant_data['last_name']))) ?>
                                </h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-1">
                                            <i class="fas fa-laptop-code me-2"></i>
                                            <strong><?= htmlspecialchars($applicant_data['program']) ?></strong>
                                        </p>
                                        <p class="mb-1">
                                            <i class="fas fa-university me-2"></i>
                                            <?= htmlspecialchars($applicant_data['campus']) ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1">
                                            <i class="fas fa-certificate me-2"></i>
                                            <?= htmlspecialchars($applicant_data['strand']) ?>
                                        </p>
                                        <p class="mb-1">
                                            <i class="fas fa-hashtag me-2"></i>
                                            Registration ID: <strong><?= $applicant_data['registration_id'] ?></strong>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Interview Form -->
                    <div class="p-4">
                        <form method="POST" action="" id="interviewForm">
                            <input type="hidden" name="applicant_id" value="<?= $applicant_id ?>">
                            
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <tr>
                                        <th style="background-color: rgb(0, 105, 42); color: white;" colspan="7">I. PREPAREDNESS FOR COLLEGE EDUCATION (20 points)</th>
                                    </tr>
                                    <tr>
                                        <th style="width: 40%;">Indicators</th>
                                        <th class="text-center">5<br><small>Excellent</small></th>
                                        <th class="text-center">4<br><small>Above Average</small></th>
                                        <th class="text-center">3<br><small>Average</small></th>
                                        <th class="text-center">2<br><small>Below Avg</small></th>
                                        <th class="text-center">1<br><small>Poor</small></th>
                                    </tr>

                                    <!-- Preparedness Rows -->
                                    <?php
                                        $section1 = [
                                          'Foundation in math, science, and other requisite skills/knowledge',
                                          'Study habits (as discussed during the interview)',
                                          'Display of interest in the applied program',
                                          'Academic and extra-curricular achievements/awards'
                                        ];
                                        foreach ($section1 as $i => $item):
                                    ?>
                                        <tr>
                                          <td class="text-start"><?= ($i+1).'. '.$item ?></td>
                                          <?php for ($score = 5; $score >= 1; $score--): ?>
                                            <td class="text-center"><input type="radio" name="section1_item<?= $i ?>" value="<?= $score ?>" required class="form-check-input"></td>
                                          <?php endfor; ?>
                                        </tr>
                                    <?php endforeach; ?>

                                    <tr>
                                        <th style="background-color: rgb(0, 105, 42); color: white;" colspan="7">II. ORAL COMMUNICATION SKILLS (20 points)</th>
                                    </tr>
                                    <?php
                                        $section2 = [
                                          'Content of the responses to interview questions',
                                          'Manner and delivery of the responses',
                                          'Mechanics, use of words/terms, grammar, and pronunciation',
                                          'Gestures and facial expressions'
                                        ];
                                        foreach ($section2 as $i => $item):
                                    ?>
                                        <tr>
                                          <td class="text-start"><?= ($i+1).'. '.$item ?></td>
                                          <?php for ($score = 5; $score >= 1; $score--): ?>
                                            <td class="text-center"><input type="radio" name="section2_item<?= $i ?>" value="<?= $score ?>" required class="form-check-input"></td>
                                          <?php endfor; ?>
                                        </tr>
                                    <?php endforeach; ?>

                                    <tr>
                                        <th style="background-color: rgb(0, 105, 42); color: white;" colspan="7">III. PERSONAL/PHYSICAL/SOCIAL TRAITS (20 points)</th>
                                    </tr>
                                    <?php
                                        $section3 = [
                                          'Personal traits (professionalism, confidence, enthusiasm)',
                                          'Social traits (courtesy, attentiveness, rapport with interviewer)',
                                          'Physical appearance (hygiene, grooming, dress/clothes)',
                                          'Body language, eye contact, and posture'
                                        ];
                                        foreach ($section3 as $i => $item):
                                    ?>
                                        <tr>
                                          <td class="text-start"><?= ($i+1).'. '.$item ?></td>
                                          <?php for ($score = 5; $score >= 1; $score--): ?>
                                            <td class="text-center"><input type="radio" name="section3_item<?= $i ?>" value="<?= $score ?>" required class="form-check-input"></td>
                                          <?php endfor; ?>
                                        </tr>
                                    <?php endforeach; ?>

                                    <tr>
                                        <th style="background-color: rgb(0, 105, 42); color: white;" colspan="7">IV. WRITING SKILLS (20 points)</th>
                                    </tr>
                                    <tr>
                                        <td class="text-start">1. Rating on Writing Skills</td>
                                        <?php for ($score = 5; $score >= 1; $score--): ?>
                                          <td class="text-center"><input type="radio" name="writing" value="<?= $score ?>" required class="form-check-input"></td>
                                        <?php endfor; ?>
                                    </tr>

                                    <tr>
                                        <th style="background-color: rgb(0, 105, 42); color: white;" colspan="7">V. READING AND COMPREHENSION (20 points)</th>
                                    </tr>
                                    <tr>
                                        <td class="text-start">1. Score on Reading and Comprehension Test</td>
                                        <td colspan="6"><input type="number" name="reading_score" min="0" max="20" maxlength="2" class="form-control" required></td>
                                    </tr>

                                    <tr style="background-color: rgba(0, 105, 42, 0.1);">
                                        <th colspan="7">TOTAL SCORE (TS): <input type="number" name="total_score" id="totalScore" readonly class="form-control d-inline-block" style="width: 100px;"></th>
                                    </tr>
                                    <tr style="background-color: rgba(0, 105, 42, 0.2);">
                                        <th colspan="7">
                                          INTERVIEW SCORE = (TS / 100) × 50 = 
                                          <input type="number" name="final_score" id="finalScore" readonly class="form-control d-inline-block" style="width: 100px;">
                                        </th>
                                    </tr>
                                </table>
                            </div>

                            <div class="text-center mt-4">
                                <button type="button" id="submitBtn" class="btn btn-save btn-lg">
                                    <i class="fas fa-save me-2"></i>Submit Evaluation
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h5>No Applicant Selected</h5>
                        <p class="text-muted">Please select an applicant from the applicants list to conduct an interview.</p>
                        <a href="interviewer_main.php?page=interviewer_applicants" class="btn btn-back">
                            <i class="fas fa-users me-2"></i>View Applicants List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-calculate total score
document.addEventListener('DOMContentLoaded', function() {
    const radioInputs = document.querySelectorAll('input[type="radio"]');
    const readingInput = document.querySelector('input[name="reading_score"]');
    const totalScoreInput = document.getElementById('totalScore');
    const finalScoreInput = document.getElementById('finalScore');
    
    function calculateScores() {
        let section1Total = 0;
        let section2Total = 0;
        let section3Total = 0;
        let writingScore = 0;
        let readingScore = parseInt(readingInput.value) || 0;
        
        // Calculate Section 1: Preparedness (4 items)
        for (let i = 0; i < 4; i++) {
            const checked = document.querySelector(`input[name="section1_item${i}"]:checked`);
            if (checked) {
                section1Total += parseInt(checked.value);
            }
        }
        
        // Calculate Section 2: Communication Skills (4 items)
        for (let i = 0; i < 4; i++) {
            const checked = document.querySelector(`input[name="section2_item${i}"]:checked`);
            if (checked) {
                section2Total += parseInt(checked.value);
            }
        }
        
        // Calculate Section 3: Personal/Physical/Social Traits (4 items)
        for (let i = 0; i < 4; i++) {
            const checked = document.querySelector(`input[name="section3_item${i}"]:checked`);
            if (checked) {
                section3Total += parseInt(checked.value);
            }
        }
        
        // Calculate Writing Skills (1 item, multiplied by 4 to get 20 points)
        const writingChecked = document.querySelector('input[name="writing"]:checked');
        if (writingChecked) {
            writingScore = parseInt(writingChecked.value) * 4;
        }
        
        // Total Score (out of 100)
        const totalScore = section1Total + section2Total + section3Total + writingScore + readingScore;
        
        // Final Interview Score (out of 50)
        const finalScore = (totalScore / 100) * 50;
        
        // Update display
        totalScoreInput.value = totalScore;
        finalScoreInput.value = finalScore.toFixed(2);
    }
    
    // Add event listeners
    radioInputs.forEach(input => {
        input.addEventListener('change', calculateScores);
    });
    
    readingInput.addEventListener('input', function() {
        // Limit to 2 digits only
        if (this.value.length > 2) {
            this.value = this.value.slice(0, 2);
        }
        // Ensure value is within range
        if (parseInt(this.value) > 20) {
            this.value = 20;
        }
        calculateScores();
    });
    
    // Initial calculation
    calculateScores();
    
    // Show toast notifications if they exist
    const successToast = document.getElementById('successToast');
    const errorToast = document.getElementById('errorToast');
    
    if (successToast) {
        const toast = new bootstrap.Toast(successToast);
        toast.show();
        
        // Remove toast from DOM after it's hidden
        successToast.addEventListener('hidden.bs.toast', function() {
            successToast.remove();
        });
        
        // Force dismiss after 5 seconds if not already dismissed
        setTimeout(() => {
            if (successToast && successToast.parentNode) {
                toast.hide();
            }
        }, 5000);
    }
    
    if (errorToast) {
        const toast = new bootstrap.Toast(errorToast);
        toast.show();
        
        // Remove toast from DOM after it's hidden
        errorToast.addEventListener('hidden.bs.toast', function() {
            errorToast.remove();
        });
        
        // Force dismiss after 7 seconds if not already dismissed
        setTimeout(() => {
            if (errorToast && errorToast.parentNode) {
                toast.hide();
            }
        }, 7000);
    }
    
    // Function to show error toast
    function showErrorToast(message) {
        let toastContainer = document.querySelector('.toast-container');
        
        // Create toast container if it doesn't exist
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '1060';
            document.body.appendChild(toastContainer);
        }
        
        const errorToast = document.createElement('div');
        errorToast.className = 'toast align-items-center text-bg-danger border-0';
        errorToast.setAttribute('role', 'alert');
        errorToast.setAttribute('aria-live', 'assertive');
        errorToast.setAttribute('aria-atomic', 'true');
        errorToast.setAttribute('data-bs-delay', '5000');
        
        errorToast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        toastContainer.appendChild(errorToast);
        const toast = new bootstrap.Toast(errorToast);
        toast.show();
        
        // Remove the toast element after it's hidden
        errorToast.addEventListener('hidden.bs.toast', function() {
            errorToast.remove();
        });
    }
    
    // Add confirmation modal for form submission
    const submitBtn = document.getElementById('submitBtn');
    const interviewForm = document.getElementById('interviewForm');
    const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    const confirmSubmitBtn = document.getElementById('confirmSubmitBtn');
    
    submitBtn.addEventListener('click', function() {
        // Check if all required fields are filled
        const allRadios = document.querySelectorAll('input[type="radio"]:required');
        const readingScore = document.querySelector('input[name="reading_score"]');
        
        let allFilled = true;
        allRadios.forEach(radio => {
            const name = radio.name;
            const checked = document.querySelector(`input[name="${name}"]:checked`);
            if (!checked) {
                allFilled = false;
            }
        });
        
        if (!readingScore.value || readingScore.value < 0 || readingScore.value > 20) {
            allFilled = false;
        }
        
        if (!allFilled) {
            showErrorToast('Please complete all evaluation fields before submitting.');
            return;
        }
        
        // Get the calculated scores for confirmation
        const totalScore = document.getElementById('totalScore').value;
        const finalScore = document.getElementById('finalScore').value;
        
        // Update modal with current scores
        document.getElementById('modalTotalScore').textContent = totalScore;
        document.getElementById('modalFinalScore').textContent = finalScore;
        
        // Show the confirmation modal
        confirmationModal.show();
    });
    
    // Handle confirmation modal submit
    confirmSubmitBtn.addEventListener('click', function() {
        // Create a hidden submit button and trigger it
        const submitInput = document.createElement('input');
        submitInput.type = 'hidden';
        submitInput.name = 'submit_interview';
        submitInput.value = '1';
        interviewForm.appendChild(submitInput);
        interviewForm.submit();
    });
});
</script>
</body>
</html>
