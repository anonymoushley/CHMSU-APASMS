<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
     session_start();
} 

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit();
}

// Check if user is a student
if ($_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

// Database connection
require_once '../config/database.php';

// Get user information
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM registration WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Function to get all submitted data for the user
function getAllSubmittedData($pdo, $user_id) {
    $data = [];
    
    // First, get the personal_info_id from registration table
    $sql = "SELECT personal_info_id FROM registration WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $registration = $stmt->fetch();
    
    if (!$registration || !$registration['personal_info_id']) {
        return $data; // Return empty array if no personal_info_id found
    }
    
    $personal_info_id = $registration['personal_info_id'];
    
    // Get personal info
    $sql = "SELECT * FROM personal_info WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$personal_info_id]);
    $data['personal_info'] = $stmt->fetch();
    
    if ($data['personal_info']) {
        // Get socio demographic data
        $sql = "SELECT * FROM socio_demographic WHERE personal_info_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$personal_info_id]);
        $data['socio_demographic'] = $stmt->fetch();
        
        // Debug: Log SQL query and result for socio_demographic
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            error_log("SQL Query: $sql with personal_info_id: $personal_info_id");
            error_log("Socio demographic result: " . print_r($data['socio_demographic'], true));
        }
        
        // Get academic background
        $sql = "SELECT * FROM academic_background WHERE personal_info_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$personal_info_id]);
        $data['academic_background'] = $stmt->fetch();
        
        // Debug: Log SQL query and result for academic_background
        if (isset($_GET['debug']) && $_GET['debug'] == '1') {
            error_log("Academic Background SQL Query: $sql with personal_info_id: $personal_info_id");
            error_log("Academic background result: " . print_r($data['academic_background'], true));
        }
        
        // Get program application
        $sql = "SELECT * FROM program_application WHERE personal_info_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$personal_info_id]);
        $data['program_application'] = $stmt->fetch();
        
        // Get documents
        $sql = "SELECT * FROM documents WHERE personal_info_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$personal_info_id]);
        $data['documents'] = $stmt->fetch();
    }
    
    return $data;
}

// Get all submitted data
$submittedData = getAllSubmittedData($pdo, $user_id);

// Check if user has any submitted data
$hasSubmittedData = !empty($submittedData['personal_info']) || 
                   !empty($submittedData['academic_background']) || 
                   !empty($submittedData['program_application']) || 
                   !empty($submittedData['documents']);

// Debug: Log the retrieved data (remove this in production)
if (isset($_GET['debug']) && $_GET['debug'] == '1') {
    echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px; border: 1px solid #ccc;'>";
    echo "<h4>Debug: Retrieved Data</h4>";
    echo "<h5>Personal Info:</h5>";
    echo "<pre>";
    print_r($submittedData['personal_info'] ?? 'No personal info found');
    echo "</pre>";
    echo "<h5>Socio Demographic:</h5>";
    echo "<pre>";
    print_r($submittedData['socio_demographic'] ?? 'No socio demographic found');
    echo "</pre>";
    echo "<h5>Academic Background:</h5>";
    echo "<pre>";
    print_r($submittedData['academic_background'] ?? 'No academic background found');
    echo "</pre>";
    echo "<h5>Program Application:</h5>";
    echo "<pre>";
    print_r($submittedData['program_application'] ?? 'No program application found');
    echo "</pre>";
    echo "<h5>Documents:</h5>";
    echo "<pre>";
    print_r($submittedData['documents'] ?? 'No documents found');
    echo "</pre>";
    
    echo "<h5>User ID:</h5>";
    echo "<pre>";
    print_r($user_id);
    echo "</pre>";
    
    echo "<h5>Personal Info ID:</h5>";
    echo "<pre>";
    if (isset($submittedData['personal_info']['id'])) {
        print_r($submittedData['personal_info']['id']);
    } else {
        echo "No personal_info ID found";
    }
    echo "</pre>";
    
    // Debug specific checkbox fields
    echo "<h5>Checkbox Field Values:</h5>";
    $checkboxFields = ['access_computer', 'access_internet', 'access_mobile', 'indigenous_group', 'first_gen_college', 'was_scholar', 'received_honors', 'has_disability'];
    foreach ($checkboxFields as $field) {
        $value = $submittedData['socio_demographic'][$field] ?? 'Not found';
        echo "<p><strong>$field:</strong> " . var_export($value, true) . " (Type: " . gettype($value) . ")</p>";
    }
    
    // Debug academic background fields
    echo "<h5>Academic Background Field Values:</h5>";
    $academicFields = ['last_school_attended', 'strand', 'year_graduated', 'g11_1st_avg', 'g11_2nd_avg', 'g12_1st_avg', 'academic_award'];
    foreach ($academicFields as $field) {
        $value = $submittedData['academic_background'][$field] ?? 'Not found';
        echo "<p><strong>$field:</strong> " . var_export($value, true) . " (Type: " . gettype($value) . ")</p>";
    }
    echo "</div>";
}

// Initialize session data if not exists
if (!isset($_SESSION['form_data'])) {
    $_SESSION['form_data'] = [
        'step1' => [],
        'step2' => [],
        'step3' => [],
        'step4' => []
    ];
}

// Get personal_info_id from session or from database via user_id
$personal_info_id = $_SESSION['personal_info_id'] ?? null;

// If not in session, get it from the database using user_id
if (!$personal_info_id && isset($user['personal_info_id'])) {
    $personal_info_id = $user['personal_info_id'];
    $_SESSION['personal_info_id'] = $personal_info_id; // Store in session for future use
}
$applicantStatus = '';
$yearGraduated = '';

if ($personal_info_id) {
    $sql = "SELECT 
                r.applicant_status, 
                a.year_graduated 
            FROM 
                registration r
            LEFT JOIN academic_background a 
                ON r.personal_info_id = a.personal_info_id
            WHERE 
                r.personal_info_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $personal_info_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $applicantStatus = $row['applicant_status'] ?? '';
    $yearGraduated = $row['year_graduated'] ?? '';
}

// Function to get saved form data
function getSavedFormData($step) {
    return isset($_SESSION['form_data']['step' . $step]) ? $_SESSION['form_data']['step' . $step] : [];
}
?>

<style>
  .progress-bar-custom {
    background-color: #00692a;
  }
  .nav-link{
    color: black;
  }
  
  .nav-link:hover {
    color: #00692a !important;
  }
  .id-picture-container {
    width: 120px;
    height: 120px;
    border: 1px dashed #ccc;
    display: flex;
    align-items: center;
    justify-content: center;
    position: absolute;
    top: 15px;
    right: 15px;
    overflow: hidden;
    background-color: #f8f9fa;
    transition: all 0.2s ease;
  }
  .id-picture-container:hover {
    background-color: #e9ecef;
    border-color: #6c757d;
    box-shadow: 0 0 5px rgba(0,0,0,0.2);
  }
  .id-picture-preview {
    max-width: 100%;
    max-height: 100%;
    display: none;
  }
  .placeholder-text {
    color: #6c757d;
    text-align: center;
    font-size: 0.8rem;
  }
  .card-body {
    position: relative;
    padding-top: 25px;
    background-color: #f8f9fa;
  }
 input.uppercase {
            text-transform: uppercase;
        }
        .container{width: 950px;}

  .id-picture-preview {
    width: 2in;
    height: 2in;
    object-fit: cover; /* Ensures the image fills the space while maintaining aspect ratio */
}

  .campus-btn {
    border: 2px solid #00692a;
    color: #00692a;
    background-color: transparent;
    transition: all 0.3s ease;
  }
  
  .campus-btn:hover {
    background-color: #00692a;
    color: white;
  }
  
  .campus-btn.selected {
    background-color: #00692a;
    color: white;
  }
  
  .college-btn {
    border: 2px solid #00692a;
    color: #00692a;
    background-color: transparent;
    transition: all 0.3s ease;
  }
  
  .college-btn:hover {
    background-color: #00692a;
    color: white;
  }
  
  .college-btn.selected {
    background-color: #00692a;
    color: white;
  }
  
  .btn-save {
    background-color: #00692a;
    border-color: #00692a;
    color: white;
    transition: all 0.3s ease;
  }
  
  .btn-save:hover {
    background-color: #005223;
    border-color: #005223;
    color: white;
  }
  
  .btn-save:focus {
    background-color: #00692a;
    border-color: #00692a;
    color: white;
    box-shadow: 0 0 0 0.2rem rgba(0, 105, 42, 0.25);
  }
  
  /* Document Modal Styles */
  
  .modal-body img {
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
  }
  
  .modal-body embed {
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
  }
  
  /* Remove number input arrows */
  input[type="number"]::-webkit-outer-spin-button,
  input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
  }
  
  input[type="number"] {
    -moz-appearance: textfield;
  }
  
  /* Green checkbox and radio button styling */
  .form-check-input:checked {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
  }
  
  .form-check-input:focus {
    border-color: #28a745 !important;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
  }
  
  .form-check-input[type="checkbox"]:checked {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
  }
  
  .form-check-input[type="radio"]:checked {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
  }
  
  /* Modal button hover effect */
  #confirmSaveBtn:hover {
    background-color: #005223 !important;
    border-color: #005223 !important;
    color: white !important;
  }
</style>

<div class="container">
  <h4>Applicant Profiling</h4>
  <?php if ($hasSubmittedData): ?>
    <div class="alert alert-success">
      <i class="fas fa-check-circle me-2"></i>
      <strong>Existing Data Found:</strong> Your previously submitted information has been loaded. You can review and update any fields as needed.
    </div>
  <?php else: ?>
    <p>Fill out your personal information, academic background, and program application details.</p>
  <?php endif; ?>

  <div class="progress mb-4">
    <div id="profileProgress" class="progress-bar progress-bar-custom" role="progressbar" style="width: 20%;" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">Step 1 of 5</div>
  </div>

  <ul class="nav nav-tabs mb-4" id="profilingTabs">
    <li class="nav-item"><a class="nav-link active" href="#" onclick="showProfilingStep(1, this)">Personal Info</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="showProfilingStep(2, this)">Academic Background</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="showProfilingStep(3, this)">Program Application</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="showProfilingStep(4, this)">Document Uploads</a></li>
    <li class="nav-item"><a class="nav-link" href="#" onclick="showProfilingStep(5, this)">Confirmation</a></li>
  </ul>
  <div id="profilingFormWrapper">
  <form id="step1Form" action="savestep1.php" method="POST" enctype="multipart/form-data">
    <div id="step1" class="card mb-4">
      <div class="card-header text-white" style="background-color: #00692a;">
        <h5 class="mb-0">Step 1: Personal Information</h5>
      </div>
      <div class="card-body">
        <div class="id-picture-container" id="previewContainer" style="cursor: pointer;" onclick="document.getElementById('idPicture').click();">
          <div class="placeholder-text">Insert 2x2 ID</div>
          <img id="picturePreview" name="id_picture" class="id-picture-preview" alt="ID Preview" 
               <?php if (isset($submittedData['personal_info']['id_picture']) && !empty($submittedData['personal_info']['id_picture'])): ?>
               src="<?php echo htmlspecialchars('../uploads/id_pictures/' . $submittedData['personal_info']['id_picture']); ?>" 
               style="display: block;"
               onerror="this.style.display='none'; document.querySelector('.placeholder-text').style.display='block'; console.log('Image failed to load:', this.src);"
               onload="this.style.display='block'; document.querySelector('.placeholder-text').style.display='none';"
               <?php endif; ?>>
        </div>

        <div class="mb-3">
          <label class="form-label">2x2 ID Picture</label>
          <input type="file" name="id_picture" id="idPicture" class="form-control" accept="image/*" style="display: none;">
          <div class="form-text">Click the preview box in the upper right to upload your 2x2 ID picture</div>
        </div>
        <div class="row mt-5">
          <div class="col-md-4 mb-3">
            <label class="form-label">Last Name</label>
            <input type="text" name="last_name" class="form-control uppercase" required value="<?php echo htmlspecialchars($submittedData['personal_info']['last_name'] ?? $user['last_name'] ?? ''); ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">First Name</label>
            <input type="text" name="first_name" class="form-control uppercase" required value="<?php echo htmlspecialchars($submittedData['personal_info']['first_name'] ?? $user['first_name'] ?? ''); ?>">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Middle Name</label>
            <input type="text" name="middle_name" class="form-control uppercase" value="<?php echo htmlspecialchars($submittedData['personal_info']['middle_name'] ?? $user['middle_name'] ?? ''); ?>">
          </div>
        </div>
        <div class="row">
          <div class="col-md-2 mb-3">
            <label class="form-label">Age</label>
            <input type="text" name="age" class="form-control" id="age" required readonly value="<?php echo htmlspecialchars($submittedData['personal_info']['age'] ?? $user['age'] ?? ''); ?>">
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label">Date of Birth</label>
            <input type="date" name="birth_date" class="form-control" id="birthDate" required onchange="calculateAge()" value="<?php echo htmlspecialchars($submittedData['personal_info']['date_of_birth'] ?? $user['birth_date'] ?? ''); ?>">
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label">Sex</label>
            <select name="sex" class="form-select" required>
              <option value="" disabled selected hidden>Select</option>
              <option value="Male" <?php echo (htmlspecialchars($submittedData['personal_info']['sex'] ?? $user['sex'] ?? '') === 'Male') ? 'selected' : ''; ?>>Male</option>
              <option value="Female" <?php echo (htmlspecialchars($submittedData['personal_info']['sex'] ?? $user['sex'] ?? '') === 'Female') ? 'selected' : ''; ?>>Female</option>
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Contact Number</label>
            <div class="input-group">
              <span class="input-group-text">+63</span>
              <input
                type="text"
                name="contact_number"
                class="form-control"
                maxlength="10"
                required
                oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);"
                value="<?php 
                  $contact = $submittedData['personal_info']['contact_number'] ?? $user['contact_number'] ?? '';
                  // Remove 0 prefix if it exists in the stored value (database stores as 0)
                  if (strpos($contact, '0') === 0) {
                    $contact = substr($contact, 1);
                  }
                  echo htmlspecialchars($contact);
                ?>"
                placeholder="9123456789"
              >
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label for="region" class="form-label">Region</label>
            <select id="region" name="region" class="form-select" required>
              <option value="" disabled selected hidden>Select Region</option>
            </select>
            <input type="hidden" name="region_name" id="region_name">
          </div>
          <div class="col-md-4 mb-3">
            <label for="province" class="form-label">Province</label>
            <select id="province" name="province" class="form-select" required disabled>
              <option value="" disabled selected hidden>No Selected Region</option>
            </select>
            <input type="hidden" name="province_name" id="province_name">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">City</label>
            <select id="city" name="city" class="form-select" required disabled>
              <option value="">No Province Selected</option>
            </select>
            <input type="hidden" name="city_name" id="city_name">
          </div>
        </div>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">Barangay</label>
            <select id="barangay" name="barangay" class="form-select" required disabled>
              <option value="">No City/Municipality Selected</option>
            </select>
            <input type="hidden" name="barangay_name" id="barangay_name">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Purok/Block/Street</label>
            <input type="text" name="street" class="form-control" required value="<?php echo htmlspecialchars($submittedData['personal_info']['street_purok'] ?? $user['address'] ?? ''); ?>">
          </div>
        </div>

        <div class="card-header text-white" style="background-color: #00692a;">
          <h5 class="mb-0">Socio Demographic Profile</h5>
        </div>
        <div class="card-body p-4 mb-4">
          <div class="mb-3">
            <label class="d-block"><b>Marital Status:</b></label>
            <div class="d-flex flex-nowrap overflow-auto gap-3 p-1">
              <?php foreach(["Single", "Married", "Divorced", "Domestic Partnership", "Others"] as $opt): ?>
                <div class="form-check form-check-inline">
                  <input type="radio" class="form-check-input" name="marital_status" value="<?= $opt ?>" required <?php echo htmlspecialchars($submittedData['socio_demographic']['marital_status'] ?? $user['marital_status'] ?? '') === $opt ? 'checked' : ''; ?>>
                  <label class="form-check-label"><?= $opt ?></label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <hr>

          <label><b>Religious Affiliation:</b></label><br>
          <?php foreach(["None", "Christianity", "Islam", "Hinduism", "Others"] as $opt): ?>
            <div class="form-check form-check-inline">
              <input type="radio" class="form-check-input" name="religion" value="<?= $opt ?>" required <?php echo htmlspecialchars($submittedData['socio_demographic']['religion'] ?? $user['religion'] ?? '') === $opt ? 'checked' : ''; ?>>
              <label class="form-check-label"><?= $opt ?></label>
            </div>
          <?php endforeach; ?>

          <hr>

          <label><b>Sexual Orientation:</b></label><br>
          <?php foreach(["Heterosexual", "Homosexual", "Bisexual", "Others"] as $opt): ?>
            <div class="form-check form-check-inline">
              <input type="radio" class="form-check-input" name="orientation" value="<?= $opt ?>" required <?php echo htmlspecialchars($submittedData['socio_demographic']['orientation'] ?? $user['orientation'] ?? '') === $opt ? 'checked' : ''; ?>>
              <label class="form-check-label"><?= $opt ?></label>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="card-header text-white" style="background-color: #00692a;">
          <h5 class="mb-0">Parental Status</h5>
        </div>
        <div class="card-body p-4 mb-4">
          <label><b>Father Status:</b></label><br>
          <?php foreach(["Alive; Away", "Alive; at Home", "Deceased", "Unknown"] as $opt): ?>
            <div class="form-check form-check-inline">
              <input type="radio" class="form-check-input" name="father_status" value="<?= $opt ?>" required <?php echo htmlspecialchars($submittedData['socio_demographic']['father_status'] ?? $user['father_status'] ?? '') === $opt ? 'checked' : ''; ?>>
              <label class="form-check-label"><?= $opt ?></label>
            </div>
          <?php endforeach; ?>

          <hr>

          <label><b>Father Education Level:</b></label><br>
          <?php foreach(["No High School Diploma", "High School Diploma", "Bachelor's Degree", "Graduate Degree"] as $opt): ?>
            <div class="form-check form-check-inline">
              <input type="radio" class="form-check-input" name="father_education" value="<?= $opt ?>" required <?php echo htmlspecialchars($submittedData['socio_demographic']['father_education'] ?? $user['father_education'] ?? '') === $opt ? 'checked' : ''; ?>>
              <label class="form-check-label"><?= $opt ?></label>
            </div>
          <?php endforeach; ?>

          <hr>

          <label><b>Father Employment:</b></label><br>
          <?php foreach(["Employed Full-Time", "Employed Part-Time", "Unemployed"] as $opt): ?>
            <div class="form-check form-check-inline">
              <input type="radio" class="form-check-input" name="father_employment" value="<?= $opt ?>" required <?php echo htmlspecialchars($submittedData['socio_demographic']['father_employment'] ?? $user['father_employment'] ?? '') === $opt ? 'checked' : ''; ?>>
              <label class="form-check-label"><?= $opt ?></label>
            </div>
          <?php endforeach; ?>

          <hr>

          <label><b>Mother Status:</b></label><br>
          <?php foreach(["Alive; Away", "Alive; at Home", "Deceased", "Unknown"] as $opt): ?>
            <div class="form-check form-check-inline">
              <input type="radio" class="form-check-input" name="mother_status" value="<?= $opt ?>" required <?php echo htmlspecialchars($submittedData['socio_demographic']['mother_status'] ?? $user['mother_status'] ?? '') === $opt ? 'checked' : ''; ?>>
              <label class="form-check-label"><?= $opt ?></label>
            </div>
          <?php endforeach; ?>

          <hr>

          <label><b>Mother Education Level:</b></label><br>
          <?php foreach(["No High School Diploma", "High School Diploma", "Bachelor's Degree", "Graduate Degree"] as $opt): ?>
            <div class="form-check form-check-inline">
              <input type="radio" class="form-check-input" name="mother_education" value="<?= $opt ?>" required <?php echo htmlspecialchars($submittedData['socio_demographic']['mother_education'] ?? $user['mother_education'] ?? '') === $opt ? 'checked' : ''; ?>>
              <label class="form-check-label"><?= $opt ?></label>
            </div>
          <?php endforeach; ?>

          <hr>

          <label><b>Mother Employment:</label></b><br>
          <?php foreach(["Employed Full-Time", "Employed Part-Time", "Unemployed"] as $opt): ?>
            <div class="form-check form-check-inline">
              <input type="radio" class="form-check-input" name="mother_employment" value="<?= $opt ?>" required <?php echo htmlspecialchars($submittedData['socio_demographic']['mother_employment'] ?? $user['mother_employment'] ?? '') === $opt ? 'checked' : ''; ?>>
              <label class="form-check-label"><?= $opt ?></label>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="card-header text-white" style="background-color: #00692a;">
          <h5 class="mb-0">Other Details</h5>
        </div>
        <div class="card-body p-4 mb-4">
          <label><b>Number of Siblings:</b></label><br>
          <?php foreach(["None", "One", "Two or more"] as $opt): ?>
            <div class="form-check form-check-inline">
              <input type="radio" class="form-check-input" name="siblings" value="<?= $opt ?>" required <?php echo htmlspecialchars($submittedData['socio_demographic']['siblings'] ?? $user['siblings'] ?? '') === $opt ? 'checked' : ''; ?>>
              <label class="form-check-label"><?= $opt ?></label>
            </div>
          <?php endforeach; ?>

          <hr>

          <label><b>Currently Living With:</b></label><br>
          <?php foreach(["Both parents", "One parent only", "Relatives", "Alone"] as $opt): ?>
            <div class="form-check form-check-inline">
              <input type="radio" class="form-check-input" name="living_with" value="<?= $opt ?>" required <?php echo htmlspecialchars($submittedData['socio_demographic']['living_with'] ?? $user['living_with'] ?? '') === $opt ? 'checked' : ''; ?>>
              <label class="form-check-label"><?= $opt ?></label>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="card-header text-white" style="background-color: #00692a;">
          <h5 class="mb-0">Technology Access</h5>
        </div>
        <div class="card-body p-4 mb-2">
          <?php
            $tech = [
              "access_computer" => "The student applicant has access to personal computer at home.",
              "access_internet" => "The student applicant has internet access at home.",
              "access_mobile" => "The student applicant has access to mobile device(s)."
            ];
            foreach ($tech as $name => $label):
          ?>
            <label><b><?= $label ?>:</b></label><br>
            <div class="form-check form-check-inline">
              <input type="radio" class="form-check-input" name="<?= $name ?>" value="Yes" required <?php 
                $value = $submittedData['socio_demographic'][$name] ?? '';
                $isChecked = ($value == '1' || $value == 1 || strtolower($value) == 'yes' || $value === 'Yes');
                if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                  echo "<!-- Debug $name: value='$value', type=" . gettype($value) . ", checked=" . ($isChecked ? 'true' : 'false') . " -->";
                }
                echo $isChecked ? 'checked' : '';
              ?>>
              <label class="form-check-label">Yes</label>
            </div>
            <div class="form-check form-check-inline mb-2">
              <input type="radio" class="form-check-input" name="<?= $name ?>" value="No" <?php 
                $value = $submittedData['socio_demographic'][$name] ?? '';
                $isChecked = ($value == '0' || $value == 0 || strtolower($value) == 'no' || $value === 'No');
                if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                  echo "<!-- Debug $name: value='$value', type=" . gettype($value) . ", checked=" . ($isChecked ? 'true' : 'false') . " -->";
                }
                echo $isChecked ? 'checked' : '';
              ?>>
              <label class="form-check-label">No</label>
            </div><br>
          <?php endforeach; ?>
        </div>
        <div class="card-header text-white" style="background-color: #00692a;">
          <h5 class="mb-0">Other Determinants</h5>
        </div>
        <div class="card-body p-4 mb-4">
          <div class="row">
            <?php
              $other = [
                "indigenous_group" => "The student applicant is a member of an indigenous group in the Philippines.",
                "first_gen_college" => "The student applicant is first in their family to attend college.",
                "was_scholar" => "The student applicant was scholar during high school.",
                "received_honors" => "The student applicant has received academic honors in high school.",
                "has_disability" => "The student applicant has a disability."
              ];

              foreach ($other as $name => $label):
            ?>
              <div class="col-md-6 mb-3">
                <label><b><?= $label ?>:</b></label><br>
                <div class="form-check form-check-inline">
                  <input type="radio" class="form-check-input" id="<?= $name ?>_yes" name="<?= $name ?>" value="Yes" <?php 
                    $value = $submittedData['socio_demographic'][$name] ?? '';
                    $isChecked = ($value == '1' || $value == 1 || strtolower($value) == 'yes' || $value === 'Yes');
                    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                      echo "<!-- Debug $name: value='$value', type=" . gettype($value) . ", checked=" . ($isChecked ? 'true' : 'false') . " -->";
                    }
                    echo $isChecked ? 'checked' : '';
                  ?> <?php echo $name == 'has_disability' ? 'onclick="toggleDisabilityDetail(true)"' : '' ?> required>
                  <label class="form-check-label">Yes</label>
                </div>
                <div class="form-check form-check-inline">
                  <input type="radio" class="form-check-input" id="<?= $name ?>_no" name="<?= $name ?>" value="No" <?php 
                    $value = $submittedData['socio_demographic'][$name] ?? '';
                    $isChecked = ($value == '0' || $value == 0 || strtolower($value) == 'no' || $value === 'No');
                    if (isset($_GET['debug']) && $_GET['debug'] == '1') {
                      echo "<!-- Debug $name: value='$value', type=" . gettype($value) . ", checked=" . ($isChecked ? 'true' : 'false') . " -->";
                    }
                    echo $isChecked ? 'checked' : '';
                  ?> <?php echo $name == 'has_disability' ? 'onclick="toggleDisabilityDetail(false)"' : '' ?>>
                  <label class="form-check-label">No</label>
                </div>
              </div>

              <?php if ($name == "has_disability"): ?>
                <div class="col-md-6 mb-3" id="disability_detail" style="display:none;">
                  <label>If yes, specify disability:</label>
                  <input type="text" class="form-control" name="disability_detail" value="<?php echo htmlspecialchars($submittedData['socio_demographic']['disability_detail'] ?? ''); ?>">
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="d-flex justify-content-end">
          <button type="submit" class="btn btn-save">Save and Continue</button>
        </div>
      </div>
    </div>
  </form>

  <form id="step2Form" action="save_step2.php" method="POST">
    <div id="step2" class="card mb-4" style="display: none;">
      <div class="card-header text-white" style="background-color: #00692a;">
        <h5 class="mb-0">Step 2: Academic Background</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label>Last School Attended</label>
            <input type="text" name="last_school_attended" class="form-control uppercase" oninput="this.value = this.value.toUpperCase()" required value="<?php echo htmlspecialchars($submittedData['academic_background']['last_school_attended'] ?? $user['last_school_attended'] ?? ''); ?>">
          </div>

          <div class="col-md-3 mb-3">
            <label>SHS Strand</label>
            <select name="strand_id" class="form-select" required>
<option value="" disabled selected hidden>Select a strand</option>
              <?php
                // Fetch strands from database
                try {
                  require_once 'db_connect.php';
                  $strand_stmt = $pdo->query("SELECT id, name FROM strands WHERE status = 'active' ORDER BY name");
                  $db_strands = $strand_stmt->fetchAll(PDO::FETCH_ASSOC);
                  
                  // Fallback to hardcoded strands if database is empty
                  if (empty($db_strands)) {
                    $db_strands = [
                      ['id' => 1, 'name' => 'STEM'],
                      ['id' => 2, 'name' => 'ABM'],
                      ['id' => 3, 'name' => 'HUMSS'],
                      ['id' => 4, 'name' => 'GAS'],
                      ['id' => 5, 'name' => 'TVL']
                    ];
                  }
                  
                  foreach ($db_strands as $strand) {
                    $selected = ($submittedData['academic_background']['strand_id'] ?? $user['strand_id'] ?? '') == $strand['id'] ? 'selected' : '';
                    echo "<option value='{$strand['id']}' $selected>{$strand['name']}</option>";
                  }
                } catch (Exception $e) {
                  // Fallback to hardcoded strands if database error
                  $strands = [
                    ['id' => 1, 'name' => 'STEM'],
                    ['id' => 2, 'name' => 'ABM'],
                    ['id' => 3, 'name' => 'HUMSS'],
                    ['id' => 4, 'name' => 'GAS'],
                    ['id' => 5, 'name' => 'TVL']
                  ];
                  foreach ($strands as $strand) {
                    $selected = ($submittedData['academic_background']['strand_id'] ?? $user['strand_id'] ?? '') == $strand['id'] ? 'selected' : '';
                    echo "<option value='{$strand['id']}' $selected>{$strand['name']}</option>";
                  }
                }
              ?>
            </select>
          </div>

          <div class="col-md-3 mb-3">
           <?php
$currentYear = date("Y");
$currentAcademicYear = 2025; // Or dynamically set this based on system config

// Check if student is a new applicant in the same academic year
$isNewApplicantSameYear = ($applicantStatus === 'New Applicant (same academic year)');

$isLocked = $isNewApplicantSameYear;
$yearGraduated = $isLocked ? $currentAcademicYear : ($submittedData['academic_background']['year_graduated'] ?? $yearGraduated ?? ''); // fallback from DB or POST
?>

<label>Year Graduated</label>
<select id="year_graduated" name="year_graduated" class="form-select" <?= $isLocked ? 'disabled' : '' ?> required>
    <option value="" disabled <?= !$yearGraduated ? 'selected hidden' : '' ?>>Select Year</option>
    <?php
    for ($year = $currentYear; $year >= 2000; $year--) {
        $selected = ($year == $yearGraduated) ? 'selected' : '';
        echo "<option value='$year' $selected>$year</option>";
    }
    ?>
</select>

<?php if ($isLocked): ?>
    <!-- Hidden input to make sure 2025 is submitted -->
    <input type="hidden" name="year_graduated" value="<?= $currentAcademicYear ?>">
<?php endif; ?>

          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label>Grade 11 1st Sem Average</label>
            <input type="number" step="0.01" min="0" max="100" name="g11_1st_avg" class="form-control grade-input" required 
                   value="<?php echo htmlspecialchars($submittedData['academic_background']['g11_1st_avg'] ?? $user['g11_1st_avg'] ?? ''); ?>"
                   placeholder="Enter grade (0-100)" pattern="^\d{1,2}(\.\d{1,2})?$">
            <div class="form-text">Enter grade from 0 to 100 (format: 99.99)</div>
          </div>
          <div class="col-md-6 mb-3">
            <label>Grade 11 2nd Sem Average</label>
            <input type="number" step="0.01" min="0" max="100" name="g11_2nd_avg" class="form-control grade-input" required 
                   value="<?php echo htmlspecialchars($submittedData['academic_background']['g11_2nd_avg'] ?? $user['g11_2nd_avg'] ?? ''); ?>"
                   placeholder="Enter grade (0-100)" pattern="^\d{1,2}(\.\d{1,2})?$">
            <div class="form-text">Enter grade from 0 to 100 (format: 99.99)</div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label>Grade 12 1st Sem Average</label>
            <input type="number" step="0.01" min="0" max="100" name="g12_1st_avg" class="form-control grade-input" required 
                   value="<?php echo htmlspecialchars($submittedData['academic_background']['g12_1st_avg'] ?? $user['g12_1st_avg'] ?? ''); ?>"
                   placeholder="Enter grade (0-100)" pattern="^\d{1,2}(\.\d{1,2})?$">
            <div class="form-text">Enter grade from 0 to 100 (format: 99.99)</div>
          </div>

          <div class="col-md-6 mb-3">
            <label>Academic Award</label>
            <select name="academic_award" class="form-select">
              <option value="">None</option>
              <option value="Honors" <?php echo htmlspecialchars($submittedData['academic_background']['academic_award'] ?? $user['academic_award'] ?? '') === 'Honors' ? 'selected' : ''; ?>>Honors</option>
              <option value="High Honors" <?php echo htmlspecialchars($submittedData['academic_background']['academic_award'] ?? $user['academic_award'] ?? '') === 'High Honors' ? 'selected' : ''; ?>>High Honors</option>
              <option value="Highest Honors" <?php echo htmlspecialchars($submittedData['academic_background']['academic_award'] ?? $user['academic_award'] ?? '') === 'Highest Honors' ? 'selected' : ''; ?>>Highest Honors</option>
            </select>
          </div>
        </div>

        <div class="d-flex justify-content-between">
          <button type="button" class="btn btn-secondary" onclick="showProfilingStep(1, document.querySelectorAll('#profilingTabs .nav-link')[0])">Previous</button>
          <button type="submit" class="btn btn-save">Save and Continue</button>
        </div>
      </div>
    </div>
  </form>

  <form id="step3Form" action="savestep3.php" method="POST">
    <div id="step3" class="card mb-4" style="display: none;">
      <div class="card-header text-white" style="background-color: #00692a;">
        <h5 class="mb-0">Step 3: Program Application</h5>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label">Select Campus</label>
          <div class="d-flex flex-wrap gap-3">
            <button class="btn campus-btn" type="button" onclick="selectCampus('Talisay', this)">Talisay Campus</button>
            <button class="btn campus-btn" type="button" onclick="selectCampus('Alijis', this)">Alijis Campus</button>
            <button class="btn campus-btn" type="button" onclick="selectCampus('Fortune', this)">Fortune Towne Campus</button>
            <button class="btn campus-btn" type="button" onclick="selectCampus('Binalbagan', this)">Binalbagan Campus</button>
          </div>
        </div>
        <input type="hidden" name="selected_campus" id="selected_campus">

        <div class="mb-3">
          <label class="form-label">Select College</label>
          <div class="d-flex flex-wrap gap-3">
            <button class="btn college-btn" type="button" onclick="selectCollege('CCS', this)">College of Computer Studies</button>
          </div>
        </div>
        <input type="hidden" name="selected_college" id="selected_college">

        <div class="mb-3">
          <label for="program" class="form-label">Select Academic Program</label>
          <select id="program" name="program" class="form-select" required>
            <option value="" disabled selected hidden>Select Program</option>
            <option value="BSIS" class="program-option" <?php echo htmlspecialchars($submittedData['program_application']['program'] ?? $user['program'] ?? '') === 'BSIS' ? 'selected' : ''; ?>>Bachelor of Science in Information Systems</option>
            <option value="BSIT" class="program-option" <?php echo htmlspecialchars($submittedData['program_application']['program'] ?? $user['program'] ?? '') === 'BSIT' ? 'selected' : ''; ?>>Bachelor of Science in Information Technology</option>
          </select>
        </div>

        <div class="d-flex justify-content-between">
          <button type="button" class="btn btn-secondary" onclick="showProfilingStep(2, document.querySelectorAll('#profilingTabs .nav-link')[1])">Previous</button>
          <button type="submit" class="btn btn-save">Save and Continue</button>
        </div>
      </div>
    </div>
  </form>

  <form id="step4Form" action="save_documents.php" method="POST" enctype="multipart/form-data">
    <div id="step4" class="card mb-4" style="display: none;">
      <div class="card-header text-white" style="background-color: #00692a;">
        <h5 class="mb-0">Step 4: Document Uploads</h5>
      </div>
      <div class="card-body">
        <?php if (isset($submittedData['documents']['g11_1st']) && !empty($submittedData['documents']['g11_1st'])): ?>
        <div class="mb-3">
          <label>Grade 11 1st Sem Report Card (Current)</label>
          <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>Current file: <?php echo htmlspecialchars($submittedData['documents']['g11_1st']); ?>
            <button type="button" class="btn btn-sm btn-outline-success ms-2" 
                    data-bs-toggle="modal" 
                    data-bs-target="#viewDocumentModal"
                    data-file="../uploads/<?php echo htmlspecialchars($submittedData['documents']['g11_1st']); ?>"
                    data-label="Grade 11 1st Sem Report Card">
              <i class="fas fa-eye me-1"></i>View
            </button>
          </div>
        </div>
        <?php else: ?>
        <!-- Debug: Show if documents data exists but g11_1st is empty -->
        <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
        <div class="alert alert-warning">
          <strong>Debug:</strong> Documents data exists: <?php echo isset($submittedData['documents']) ? 'Yes' : 'No'; ?><br>
          g11_1st value: <?php echo isset($submittedData['documents']['g11_1st']) ? htmlspecialchars($submittedData['documents']['g11_1st']) : 'Not set'; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        <div class="mb-3">
          <label>Grade 11 1st Sem Report Card<?php echo isset($submittedData['documents']['g11_1st']) && !empty($submittedData['documents']['g11_1st']) ? ' (Replace)' : ''; ?></label>
          <input type="file" id="fileUpload" name="g11_1st" class="form-control" <?php echo !isset($submittedData['documents']['g11_1st']) || empty($submittedData['documents']['g11_1st']) ? 'required' : ''; ?>>
        </div>
        <?php if (isset($submittedData['documents']['g11_2nd']) && !empty($submittedData['documents']['g11_2nd'])): ?>
        <div class="mb-3">
          <label>Grade 11 2nd Sem Report Card (Current)</label>
          <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>Current file: <?php echo htmlspecialchars($submittedData['documents']['g11_2nd']); ?>
            <button type="button" class="btn btn-sm btn-outline-success ms-2" 
                    data-bs-toggle="modal" 
                    data-bs-target="#viewDocumentModal"
                    data-file="../uploads/<?php echo htmlspecialchars($submittedData['documents']['g11_2nd']); ?>"
                    data-label="Grade 11 2nd Sem Report Card">
              <i class="fas fa-eye me-1"></i>View
            </button>
          </div>
        </div>
        <?php endif; ?>
        <div class="mb-3">
          <label>Grade 11 2nd Sem Report Card<?php echo isset($submittedData['documents']['g11_2nd']) && !empty($submittedData['documents']['g11_2nd']) ? ' (Replace)' : ''; ?></label>
          <input type="file" id="fileUpload" name="g11_2nd" class="form-control" <?php echo !isset($submittedData['documents']['g11_2nd']) || empty($submittedData['documents']['g11_2nd']) ? 'required' : ''; ?>>
        </div>
        
        <?php if (isset($submittedData['documents']['g12_1st']) && !empty($submittedData['documents']['g12_1st'])): ?>
        <div class="mb-3">
          <label>Grade 12 1st Sem Report Card (Current)</label>
          <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>Current file: <?php echo htmlspecialchars($submittedData['documents']['g12_1st']); ?>
            <button type="button" class="btn btn-sm btn-outline-success ms-2" 
                    data-bs-toggle="modal" 
                    data-bs-target="#viewDocumentModal"
                    data-file="../uploads/<?php echo htmlspecialchars($submittedData['documents']['g12_1st']); ?>"
                    data-label="Grade 12 1st Sem Report Card">
              <i class="fas fa-eye me-1"></i>View
            </button>
          </div>
        </div>
        <?php endif; ?>
        <div class="mb-3">
          <label>Grade 12 1st Sem Report Card<?php echo isset($submittedData['documents']['g12_1st']) && !empty($submittedData['documents']['g12_1st']) ? ' (Replace)' : ''; ?></label>
          <input type="file" id="fileUpload" name="g12_1st" class="form-control" <?php echo !isset($submittedData['documents']['g12_1st']) || empty($submittedData['documents']['g12_1st']) ? 'required' : ''; ?>>
        </div>
        
        <?php if (isset($submittedData['documents']['ncii']) && !empty($submittedData['documents']['ncii'])): ?>
        <div class="mb-3">
          <label>NC II Certificate (Current)</label>
          <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>Current file: <?php echo htmlspecialchars($submittedData['documents']['ncii']); ?>
            <button type="button" class="btn btn-sm btn-outline-success ms-2" 
                    data-bs-toggle="modal" 
                    data-bs-target="#viewDocumentModal"
                    data-file="../uploads/<?php echo htmlspecialchars($submittedData['documents']['ncii']); ?>"
                    data-label="NC II Certificate">
              <i class="fas fa-eye me-1"></i>View
            </button>
          </div>
        </div>
        <?php endif; ?>
        <div class="mb-3">
          <label>NC II Certificate (if any)<?php echo isset($submittedData['documents']['ncii']) && !empty($submittedData['documents']['ncii']) ? ' (Replace)' : ''; ?></label>
          <input type="file" id="fileUpload" name="ncii" class="form-control">
        </div>
        
        <?php if (isset($submittedData['documents']['guidance_cert']) && !empty($submittedData['documents']['guidance_cert'])): ?>
        <div class="mb-3">
          <label>Certification from Guidance Office (Current)</label>
          <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>Current file: <?php echo htmlspecialchars($submittedData['documents']['guidance_cert']); ?>
            <button type="button" class="btn btn-sm btn-outline-success ms-2" 
                    data-bs-toggle="modal" 
                    data-bs-target="#viewDocumentModal"
                    data-file="../uploads/<?php echo htmlspecialchars($submittedData['documents']['guidance_cert']); ?>"
                    data-label="Certification from Guidance Office">
              <i class="fas fa-eye me-1"></i>View
            </button>
          </div>
        </div>
        <?php endif; ?>
        <div class="mb-3">
          <label>Certification from Guidance Office (if any)<?php echo isset($submittedData['documents']['guidance_cert']) && !empty($submittedData['documents']['guidance_cert']) ? ' (Replace)' : ''; ?></label>
          <input type="file" id="fileUpload" name="guidance_cert" class="form-control">
        </div>
        
        <?php if (isset($submittedData['documents']['additional_file']) && !empty($submittedData['documents']['additional_file'])): ?>
        <div class="mb-3">
          <label>Additional File (Current)</label>
          <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>Current file: <?php echo htmlspecialchars($submittedData['documents']['additional_file']); ?>
            <button type="button" class="btn btn-sm btn-outline-success ms-2" 
                    data-bs-toggle="modal" 
                    data-bs-target="#viewDocumentModal"
                    data-file="../uploads/<?php echo htmlspecialchars($submittedData['documents']['additional_file']); ?>"
                    data-label="Additional File">
              <i class="fas fa-eye me-1"></i>View
            </button>
          </div>
        </div>
        <?php endif; ?>
        <div class="mb-3">
          <label>Additional File (optional)<?php echo isset($submittedData['documents']['additional_file']) && !empty($submittedData['documents']['additional_file']) ? ' (Replace)' : ''; ?></label>
          <input type="file" id="fileUpload" name="additional_file" class="form-control">
        </div>

        <div class="d-flex justify-content-between">
          <button type="button" class="btn btn-secondary" onclick="showProfilingStep(3, document.querySelectorAll('#profilingTabs .nav-link')[2])">Previous</button>
          <button type="submit" class="btn btn-save">Save and Continue</button>
        </div>
      </div>
    </div>
  </form>
</div>
<?php

// Check if application is already submitted
$stmt = $pdo->prepare("SELECT application_submitted FROM registration WHERE id = ? LIMIT 1");
$stmt->execute([$user_id]);
$appStatus = $stmt->fetch();
$applicationSubmitted = $appStatus && $appStatus['application_submitted'] == 1;

// Removed application submission blocking - users can now edit their forms even after submission
?>

  <form id="step5Form" action="submit_application.php" method="POST">
    <div id="step5" class="card mb-4" style="display: none;">
      <div class="card-header text-white" style="background-color: #00692a;">
        <h5 class="mb-0">Step 5: Confirmation</h5>
      </div>
      <div class="card-body">
        <p>Please review your information before submitting. You may go back to previous steps to make changes.</p>
        
        
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="certify" name="certify" required>
          <label class="form-check-label" for="certify">
            I certify that the information provided is true and correct.
          </label>
        </div>
        <div class="d-flex justify-content-between">
          <button type="button" class="btn btn-secondary" onclick="showProfilingStep(4, document.querySelectorAll('#profilingTabs .nav-link')[3])">Previous</button>
          <button type="submit" class="btn btn-save">Submit Application</button>
        </div>
      </div>
    </div>
  </form>
</div>

<!-- Document View Modal -->
<div class="modal fade" id="viewDocumentModal" tabindex="-1" aria-labelledby="viewDocumentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #00692a; color: white;">
        <h5 class="modal-title" id="viewDocumentModalLabel">
          <i class="fas fa-file-alt me-2"></i>View Document
        </h5>
      </div>
      <div class="modal-body text-center" id="documentModalBody">
        <!-- Content will be loaded by JavaScript -->
        <div class="d-flex justify-content-center align-items-center" style="min-height: 400px;">
          <div class="spinner-border text-success" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="fas fa-times me-1"></i>Close
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Save and Continue Confirmation Modal -->
<div class="modal fade" id="saveConfirmModal" tabindex="-1" aria-labelledby="saveConfirmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header text-white" style="background-color: #00692a;">
        <h5 class="modal-title" id="saveConfirmModalLabel">
          <i class="fas fa-save me-2"></i>Save and Continue
        </h5>
      </div>
      <div class="modal-body">
        <div class="text-center mb-3">
          <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
        </div>
        <h6 class="text-center mb-3" id="saveConfirmMessage">Are you sure you want to save this step and continue?</h6>
        <div class="alert alert-success">
          <i class="fas fa-info-circle me-2"></i>
          <strong>What happens next:</strong>
          <ul class="mb-0 mt-2">
            <li>Your current step will be saved</li>
            <li>You'll be taken to the next step</li>
            <li>You can always go back to edit previous steps</li>
          </ul>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="fas fa-times me-1"></i>Cancel
        </button>
        <button type="button" class="btn" id="confirmSaveBtn" style="background-color: #00692a; color: white; border: 1px solid #00692a;">
          <i class="fas fa-save me-1"></i>Yes, Save and Continue
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Submit Application Confirmation Modal -->
<div class="modal fade" id="submitConfirmModal" tabindex="-1" aria-labelledby="submitConfirmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header text-white" style="background-color: #00692a;">
        <h5 class="modal-title" id="submitConfirmModalLabel">
          <i class="fas fa-check-circle me-2"></i>Confirm Application Submission
        </h5>
      </div>
      <div class="modal-body">
        <div class="text-center mb-3">
          <i class="fas fa-paper-plane text-success" style="font-size: 3rem;"></i>
        </div>
        <h6 class="text-center mb-3">Are you sure you want to submit your application?</h6>
        <div class="alert alert-success">
          <i class="fas fa-info-circle me-2"></i>
          <strong>Please review:</strong>
          <ul class="mb-0 mt-2">
            <li>All information is accurate and complete</li>
            <li>All required documents are uploaded</li>
            <li>You understand this action will submit your application for review</li>
          </ul>
        </div>
        <p class="text-muted text-center small">
          <i class="fas fa-edit me-1"></i>
          <strong>Note:</strong> You can still edit your application after submission if needed.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="fas fa-times me-1"></i>Cancel
        </button>
        <button type="button" class="btn btn-success" id="confirmSubmitBtn">
          <i class="fas fa-check me-1"></i>Yes, Submit Application
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Make personal_info_id available to JavaScript
const personalInfoId = <?php echo json_encode($personal_info_id); ?>;

// Debug logging
console.log('Page loaded - personalInfoId:', personalInfoId);
console.log('Page loaded - personalInfoId type:', typeof personalInfoId);

document.addEventListener('DOMContentLoaded', function() {
    // Check if there's a stored step from a file upload refresh
    const storedStep = sessionStorage.getItem('currentStep');
    const storedMessage = sessionStorage.getItem('successMessage');
    
    if (storedStep) {
        // Navigate to the stored step
        const stepNumber = parseInt(storedStep);
        const tabElement = document.querySelectorAll('#profilingTabs .nav-link')[stepNumber - 1];
        if (tabElement) {
            showProfilingStep(stepNumber, tabElement);
            // Clear the stored step
            sessionStorage.removeItem('currentStep');
            
            // Show success message if stored
            if (storedMessage) {
                showSuccessModal(storedMessage);
                sessionStorage.removeItem('successMessage');
            }
        } else {
            // Fallback to step 1 if stored step is invalid
            showProfilingStep(1, document.querySelector('#profilingTabs .nav-link'));
        }
    } else {
        // Initialize the first step
        showProfilingStep(1, document.querySelector('#profilingTabs .nav-link'));
    }
    
    // Set campus and college selections if data exists
    <?php if (isset($submittedData['program_application']['campus'])): ?>
        const campus = '<?php echo addslashes($submittedData['program_application']['campus']); ?>';
        const campusBtn = document.querySelector(`[onclick*="'${campus}'"]`);
        if (campusBtn) {
            selectCampus(campus, campusBtn);
        }
    <?php endif; ?>
    
    <?php if (isset($submittedData['program_application']['college'])): ?>
        const college = '<?php echo addslashes($submittedData['program_application']['college']); ?>';
        const collegeBtn = document.querySelector(`[onclick*="'${college}'"]`);
        if (collegeBtn) {
            selectCollege(college, collegeBtn);
        }
    <?php endif; ?>
    
    // Handle disability detail visibility
    <?php if (isset($submittedData['socio_demographic']['has_disability']) && ($submittedData['socio_demographic']['has_disability'] == '1' || $submittedData['socio_demographic']['has_disability'] === 'Yes')): ?>
        toggleDisabilityDetail(true);
    <?php endif; ?>
    
    // Populate address fields with existing data
    <?php if (isset($submittedData['personal_info']['region']) && !empty($submittedData['personal_info']['region'])): ?>
        populateAddressFields('<?php echo addslashes($submittedData['personal_info']['region']); ?>', 
                             '<?php echo addslashes($submittedData['personal_info']['province']); ?>', 
                             '<?php echo addslashes($submittedData['personal_info']['city']); ?>', 
                             '<?php echo addslashes($submittedData['personal_info']['barangay']); ?>');
    <?php endif; ?>
});

function showProfilingStep(stepNumber, clickedTab) {
    // Hide all step divs
    document.querySelectorAll('[id^="step"]').forEach(card => {
        if (card.id.startsWith('step') && !isNaN(card.id.replace('step', ''))) {
            card.style.display = 'none';
        }
    });

    // Show selected step div
    const selectedStep = document.getElementById('step' + stepNumber);
    if (selectedStep) {
        selectedStep.style.display = 'block';
    }

    // Update active tab
    document.querySelectorAll('#profilingTabs .nav-link').forEach(tab => tab.classList.remove('active'));
    clickedTab.classList.add('active');

    // Update progress bar
    const progressBar = document.getElementById('profileProgress');
    const percent = stepNumber * 20;
    progressBar.style.width = percent + '%';
    progressBar.setAttribute('aria-valuenow', percent);
    progressBar.innerText = 'Step ' + stepNumber + ' of 5';
}

function toggleDisabilityDetail(show) {
    const detail = document.getElementById("disability_detail");
    const input = detail.querySelector('input');

    if (show) {
        detail.style.display = "block";
        input.setAttribute("required", "required");
    } else {
        detail.style.display = "none";
        input.removeAttribute("required");
        input.value = '';
    }
}

// Initialize preview functionality for ID picture
// Function to check if image exists and handle display
function checkImageExists(img, placeholder) {
    img.onload = function() {
        img.style.display = 'block';
        placeholder.style.display = 'none';
    };
    
    img.onerror = function() {
        img.style.display = 'none';
        placeholder.style.display = 'block';
        console.log('Image failed to load:', img.src);
    };
}

document.getElementById('idPicture').addEventListener('change', function(e) {
    const preview = document.getElementById('picturePreview');
    const placeholder = document.querySelector('.placeholder-text');

    if (this.files && this.files[0]) {
        const reader = new FileReader();

        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        }

        reader.readAsDataURL(this.files[0]);
    }
});

// Check existing image on page load
document.addEventListener('DOMContentLoaded', function() {
    const preview = document.getElementById('picturePreview');
    const placeholder = document.querySelector('.placeholder-text');
    
    if (preview.src && preview.src !== '') {
        console.log('Checking existing image:', preview.src);
        checkImageExists(preview, placeholder);
    } else {
        console.log('No existing image found');
    }
});

function calculateAge() {
    const birthDateInput = document.getElementById('birthDate').value;
    const ageInput = document.getElementById('age');

    if (birthDateInput) {
        const today = new Date();
        const birthDate = new Date(birthDateInput);

        let age = today.getFullYear() - birthDate.getFullYear();
        const m = today.getMonth() - birthDate.getMonth();

        if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }

        ageInput.value = age;
    } else {
        ageInput.value = '';
    }
}

function toUpperCaseInput(input) {
    input.value = input.value.toUpperCase();
}

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll('.all-uppercase').forEach(input => {
        input.addEventListener('blur', () => toUpperCaseInput(input));
    });
});

// Form submission handlers
document.getElementById('step1Form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Check if ID picture is required and not uploaded
    const idPictureInput = document.getElementById('idPicture');
    const hasExistingPicture = document.getElementById('picturePreview').src && 
                               !document.getElementById('picturePreview').src.includes('data:');
    
    if ((!idPictureInput.files || idPictureInput.files.length === 0) && !hasExistingPicture) {
        showToast('Please upload a 2x2 ID picture before continuing.', 'warning');
        return;
    }
    
    // Show confirmation modal
    document.getElementById('saveConfirmMessage').textContent = 'Are you sure you want to save Step 1 (Personal Information) and continue to Step 2?';
    const modal = new bootstrap.Modal(document.getElementById('saveConfirmModal'));
    modal.show();
    
    // Store form reference for later use
    window.currentForm = this;
    window.currentStep = 1;
});

document.getElementById('step2Form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Show confirmation modal
    document.getElementById('saveConfirmMessage').textContent = 'Are you sure you want to save Step 2 (Academic Background) and continue to Step 3?';
    const modal = new bootstrap.Modal(document.getElementById('saveConfirmModal'));
    modal.show();
    
    // Store form reference for later use
    window.currentForm = this;
    window.currentStep = 2;
});

        function checkImage() {
            let fileInput = document.getElementById("idPicture");
            if (!fileInput.files.length) {
                showToast("Please upload an image before submitting!", "warning");
                return false; // Prevent form submission
            }
            return true;
        }


document.getElementById('step3Form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Show confirmation modal
    document.getElementById('saveConfirmMessage').textContent = 'Are you sure you want to save Step 3 (Program Application) and continue to Step 4?';
    const modal = new bootstrap.Modal(document.getElementById('saveConfirmModal'));
    modal.show();
    
    // Store form reference for later use
    window.currentForm = this;
    window.currentStep = 3;
});

document.getElementById('step4Form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Show confirmation modal
    document.getElementById('saveConfirmMessage').textContent = 'Are you sure you want to save Step 4 (Document Uploads) and continue to Step 5?';
    const modal = new bootstrap.Modal(document.getElementById('saveConfirmModal'));
    modal.show();
    
    // Store form reference for later use
    window.currentForm = this;
    window.currentStep = 4;
});

document.getElementById('step5Form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Check if certify checkbox is checked
    const certifyCheckbox = document.getElementById('certify');
    if (!certifyCheckbox.checked) {
        showToast('Please certify that the information provided is true and correct before submitting.', 'warning');
        return;
    }
    
    // Show confirmation modal
    const modal = new bootstrap.Modal(document.getElementById('submitConfirmModal'));
    modal.show();
});

// Handle save confirmation modal button
document.getElementById('confirmSaveBtn').addEventListener('click', function() {
    // Close the modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('saveConfirmModal'));
    modal.hide();
    
    // Get the form and process based on current step
    const form = window.currentForm;
    const step = window.currentStep;
    const formData = new FormData(form);
    
    // Add personal_info_id to formData for steps 2, 3, 4 if it exists
    if (step > 1) {
        console.log('Step ' + step + ' - personalInfoId:', personalInfoId);
        if (personalInfoId) {
            formData.append('personal_info_id', personalInfoId);
            console.log('Added personal_info_id to formData:', personalInfoId);
        } else {
            console.log('No personalInfoId available for step ' + step);
        }
    }
    
    // Determine the endpoint based on step
    let endpoint = '';
    let successMessage = '';
    let nextStep = step + 1;
    
    switch(step) {
        case 1:
            endpoint = 'savestep1.php';
            successMessage = 'Step 1 saved successfully!';
            break;
        case 2:
            endpoint = 'save_step2.php';
            successMessage = 'Step 2 saved successfully!';
            break;
        case 3:
            endpoint = 'savestep3.php';
            successMessage = 'Step 3 saved successfully!';
            break;
        case 4:
            endpoint = 'save_documents.php';
            successMessage = 'Step 4 saved successfully!';
            break;
    }
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
    submitBtn.disabled = true;
    
    // Submit the form
    fetch(endpoint, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Store personal_info_id in sessionStorage for step 1
            if (data.personal_info_id) {
                sessionStorage.setItem('personal_info_id', data.personal_info_id);
            }
            // Reset button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            
            // Special handling for file upload steps - refresh page to show uploaded files
            if (step === 1 || step === 4) {
                // Store next step and success message in sessionStorage before refresh
                sessionStorage.setItem('currentStep', nextStep);
                sessionStorage.setItem('successMessage', successMessage);
                setTimeout(() => {
                    window.location.reload();
                }, 100); // Quick refresh to show uploaded files
            } else {
                // Show success message and navigate to next step for other steps
                showSuccessModal(successMessage);
                if (nextStep <= 5) {
                    showProfilingStep(nextStep, document.querySelectorAll('#profilingTabs .nav-link')[nextStep-1]);
                }
            }
        } else {
            showToast('Error: ' + data.message, 'error');
            // Reset button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while saving the data.', 'error');
        // Reset button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

// Handle confirmation modal submit button
document.getElementById('confirmSubmitBtn').addEventListener('click', function() {
    // Close the modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('submitConfirmModal'));
    modal.hide();
    
    // Get the form data
    const form = document.getElementById('step5Form');
    const formData = new FormData(form);
    formData.append('step', 'step5');
    formData.append('certify', 'true');
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Submitting...';
    submitBtn.disabled = true;
    
    // Submit the form
    fetch('submit_application.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            // Reset button state before showing success
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            showSuccessModal('Application submitted successfully!');
            // Redirect to the same page to show updated data
            setTimeout(() => {
                window.location.href = window.location.href;
            }, 2000);
        } else {
            showToast('Error: ' + data.message, 'error');
            // Reset button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while submitting the application.', 'error');
        // Reset button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

// Location API handlers
fetch('https://psgc.gitlab.io/api/regions/')
    .then(response => response.json())
    .then(data => {
        const regionSelect = document.getElementById('region');
        regionSelect.innerHTML = '<option value="" disabled selected hidden>Select Region</option>';
        data.forEach(region => {
            const option = document.createElement('option');
            option.value = region.code;
            option.text = region.name;
            option.dataset.name = region.name;
            regionSelect.appendChild(option);
        });
    })
    .catch(error => console.error('Error fetching regions:', error));

document.getElementById('region').addEventListener('change', function () {
    const regionCode = this.value;
    const regionName = this.options[this.selectedIndex].dataset.name;
    document.getElementById('region_name').value = regionName;
    
    const provinceSelect = document.getElementById('province');
    const citySelect = document.getElementById('city');
    const barangaySelect = document.getElementById('barangay');

    provinceSelect.innerHTML = '<option value="" disabled selected hidden>Loading Provinces...</option>';
    citySelect.innerHTML = '<option value="">No Province Selected</option>';
    barangaySelect.innerHTML = '<option value="">No City/Municipality Selected</option>';
    citySelect.disabled = true;
    barangaySelect.disabled = true;

    fetch(`https://psgc.gitlab.io/api/regions/${regionCode}/provinces/`)
        .then(response => response.json())
        .then(data => {
            provinceSelect.innerHTML = '<option value="" disabled selected hidden>Select Province</option>';
            data.forEach(province => {
                const option = document.createElement('option');
                option.value = province.code;
                option.text = province.name;
                option.dataset.name = province.name;
                provinceSelect.appendChild(option);
            });
            provinceSelect.disabled = false;
        })
        .catch(error => console.error('Error fetching provinces:', error));
});

document.getElementById('province').addEventListener('change', function () {
    const provinceCode = this.value;
    const provinceName = this.options[this.selectedIndex].dataset.name;
    document.getElementById('province_name').value = provinceName;
    
    const citySelect = document.getElementById('city');
    const barangaySelect = document.getElementById('barangay');

    citySelect.innerHTML = '<option value="" disabled selected hidden>Loading Cities/Municipalities...</option>';
    barangaySelect.innerHTML = '<option value="">No City/Municipality Selected</option>';
    barangaySelect.disabled = true;

    fetch(`https://psgc.gitlab.io/api/provinces/${provinceCode}/cities-municipalities/`)
        .then(response => response.json())
        .then(data => {
            citySelect.innerHTML = '<option value="" disabled selected hidden>Select City/Municipality</option>';
            data.forEach(city => {
                const option = document.createElement('option');
                option.value = city.code;
                option.text = city.name;
                option.dataset.name = city.name;
                citySelect.appendChild(option);
            });
            citySelect.disabled = false;
        })
        .catch(error => console.error('Error fetching cities/municipalities:', error));
});

document.getElementById('city').addEventListener('change', function () {
    const cityCode = this.value;
    const cityName = this.options[this.selectedIndex].dataset.name;
    document.getElementById('city_name').value = cityName;
    
    const barangaySelect = document.getElementById('barangay');

    barangaySelect.innerHTML = '<option value="" disabled selected hidden>Loading Barangays...</option>';

    fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays/`)
        .then(response => response.json())
        .then(data => {
            barangaySelect.innerHTML = '<option value="" disabled selected hidden>Select Barangay</option>';
            data.forEach(barangay => {
                const option = document.createElement('option');
                option.value = barangay.code;
                option.text = barangay.name;
                option.dataset.name = barangay.name;
                barangaySelect.appendChild(option);
            });
            barangaySelect.disabled = false;
        })
        .catch(error => console.error('Error fetching barangays:', error));
});

document.getElementById('barangay').addEventListener('change', function () {
    const barangayName = this.options[this.selectedIndex].dataset.name;
    document.getElementById('barangay_name').value = barangayName;
});

function selectCampus(campus, btn) {
    document.getElementById('selected_campus').value = campus;

    // Highlight selected button
    document.querySelectorAll('.campus-btn').forEach(button => {
        button.classList.remove('selected');
    });
    btn.classList.add('selected');

    // Handle BSIT visibility
    const programSelect = document.getElementById('program');
    const bsitOption = [...programSelect.options].find(opt => opt.value === 'BSIT');

    if (campus === 'Alijis' || campus === 'Binalbagan') {
        bsitOption.style.display = 'block';
    } else {
        bsitOption.style.display = 'none';
        if (programSelect.value === 'BSIT') {
            programSelect.value = '';
        }
    }
}

function selectCollege(college, btn) {
    document.getElementById('selected_college').value = college;

    // Highlight selected college button
    document.querySelectorAll('.college-btn').forEach(button => {
        button.classList.remove('selected');
    });
    btn.classList.add('selected');
}

// Function to populate address fields with existing data
function populateAddressFields(regionName, provinceName, cityName, barangayName) {
    // Wait for regions to load, then find and select the region
    setTimeout(() => {
        const regionSelect = document.getElementById('region');
        if (!regionSelect) return;
        
        const regionOptions = regionSelect.options;
        let regionFound = false;
        
        for (let i = 0; i < regionOptions.length; i++) {
            if (regionOptions[i].dataset.name === regionName) {
                regionSelect.value = regionOptions[i].value;
                document.getElementById('region_name').value = regionName;
                regionFound = true;
                
                // Trigger region change to load provinces
                regionSelect.dispatchEvent(new Event('change'));
                
                // Wait for provinces to load, then select province
                setTimeout(() => {
                    const provinceSelect = document.getElementById('province');
                    if (!provinceSelect) return;
                    
                    const provinceOptions = provinceSelect.options;
                    let provinceFound = false;
                    
                    for (let j = 0; j < provinceOptions.length; j++) {
                        if (provinceOptions[j].dataset.name === provinceName) {
                            provinceSelect.value = provinceOptions[j].value;
                            document.getElementById('province_name').value = provinceName;
                            provinceFound = true;
                            
                            // Trigger province change to load cities
                            provinceSelect.dispatchEvent(new Event('change'));
                            
                            // Wait for cities to load, then select city
                            setTimeout(() => {
                                const citySelect = document.getElementById('city');
                                if (!citySelect) return;
                                
                                const cityOptions = citySelect.options;
                                let cityFound = false;
                                
                                for (let k = 0; k < cityOptions.length; k++) {
                                    if (cityOptions[k].dataset.name === cityName) {
                                        citySelect.value = cityOptions[k].value;
                                        document.getElementById('city_name').value = cityName;
                                        cityFound = true;
                                        
                                        // Trigger city change to load barangays
                                        citySelect.dispatchEvent(new Event('change'));
                                        
                                        // Wait for barangays to load, then select barangay
                                        setTimeout(() => {
                                            const barangaySelect = document.getElementById('barangay');
                                            if (!barangaySelect) return;
                                            
                                            const barangayOptions = barangaySelect.options;
                                            
                                            for (let l = 0; l < barangayOptions.length; l++) {
                                                if (barangayOptions[l].dataset.name === barangayName) {
                                                    barangaySelect.value = barangayOptions[l].value;
                                                    document.getElementById('barangay_name').value = barangayName;
                                                    break;
                                                }
                                            }
                                        }, 1000);
                                        break;
                                    }
                                }
                                
                                if (!cityFound) {
                                    console.log('City not found:', cityName);
                                }
                            }, 1000);
                            break;
                        }
                    }
                    
                    if (!provinceFound) {
                        console.log('Province not found:', provinceName);
                    }
                }, 1000);
                break;
            }
        }
        
        if (!regionFound) {
            console.log('Region not found:', regionName);
        }
    }, 1000);
}

document.getElementById("fileUpload").addEventListener("change", function () {
    const file = this.files[0];
    const allowedTypes = ['image/jpeg', 'image/png'];
    
    if (file && !allowedTypes.includes(file.type)) {
        showToast("Only JPEG and PNG images are allowed.", "error");
        this.value = ""; // Clear the invalid file
    }
});

// Toast Notification Function
function showToast(message, type = 'info') {
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 350px;
        `;
        document.body.appendChild(toastContainer);
    }
    
    // Generate unique ID for this toast
    const toastId = 'toast-' + Date.now();
    
    // Determine toast styling based on type
    let bgColor, icon, textColor;
    switch(type) {
        case 'success':
            bgColor = '#d4edda';
            icon = 'fas fa-check-circle';
            textColor = '#155724';
            break;
        case 'error':
            bgColor = '#f8d7da';
            icon = 'fas fa-exclamation-circle';
            textColor = '#721c24';
            break;
        case 'warning':
            bgColor = '#fff3cd';
            icon = 'fas fa-exclamation-triangle';
            textColor = '#856404';
            break;
        default:
            bgColor = '#d1ecf1';
            icon = 'fas fa-info-circle';
            textColor = '#0c5460';
    }
    
    // Create toast HTML
    const toastHTML = `
        <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true" style="margin-bottom: 10px;">
            <div class="toast-header" style="background-color: ${bgColor}; color: ${textColor}; border-bottom: 1px solid rgba(0,0,0,0.1);">
                <i class="${icon} me-2"></i>
                <strong class="me-auto">Notification</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" style="background-color: ${bgColor}; color: ${textColor};">
                ${message}
            </div>
        </div>
    `;
    
    // Add toast to container
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    // Initialize and show toast
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 4000
    });
    
    toast.show();
    
    // Remove toast element after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

// Success Modal Function
function showSuccessModal(message) {
    const modalHTML = `
        <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header" style="background-color: #00692a; color: white;">
                        <h5 class="modal-title" id="successModalLabel">
                            <i class="fas fa-check-circle me-2"></i>Success
                        </h5>
                    </div>
                    <div class="modal-body text-center">
                        <i class="fas fa-check-circle text-success" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <p class="fs-5">${message}</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('successModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('successModal'));
    modal.show();
    
    // Auto-hide after 2 seconds
    setTimeout(() => {
        modal.hide();
    }, 2000);
}

// Document View Modal functionality
document.addEventListener('DOMContentLoaded', function() {
    var viewModal = document.getElementById('viewDocumentModal');
    
    if (viewModal) {
        viewModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var file = button.getAttribute('data-file');
            var label = button.getAttribute('data-label');
            var modalTitle = viewModal.querySelector('.modal-title');
            var modalBody = document.getElementById('documentModalBody');
            
            // Store current file for download
            currentFile = file;
            
            // Update modal title
            modalTitle.innerHTML = '<i class="fas fa-file-alt me-2"></i>View: ' + label;
            
            // Show loading spinner
            modalBody.innerHTML = '<div class="d-flex justify-content-center align-items-center" style="min-height: 400px;"><div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            
            // Debug: Log file path
            console.log('Loading file:', file);
            
            // Determine file type and load content
            var ext = file.split('.').pop().toLowerCase();
            
            setTimeout(() => {
                if(['jpg','jpeg','png','gif','bmp','webp'].includes(ext)) {
                    // Create image element with proper loading handling
                    var img = document.createElement('img');
                    img.className = 'img-fluid';
                    img.alt = 'Document Image';
                    img.style.maxHeight = '70vh';
                    img.style.width = 'auto';
                    img.style.borderRadius = '8px';
                    img.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
                    
                    // Handle image load
                    img.onload = function() {
                        console.log('Image loaded successfully:', file);
                        modalBody.innerHTML = '';
                        modalBody.appendChild(img);
                    };
                    
                    // Handle image error
                    img.onerror = function() {
                        console.error('Failed to load image:', file);
                        modalBody.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Failed to load image. <a href="' + file + '" target="_blank" class="btn btn-success ms-2"><i class="fas fa-external-link-alt me-1"></i>Open in New Tab</a></div>';
                    };
                    
                    // Set image source
                    console.log('Setting image source:', file);
                    img.src = file;
                    
                } else if(ext === 'pdf') {
                    modalBody.innerHTML = '<embed src="' + file + '" type="application/pdf" width="100%" height="500px" style="border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);" />';
                } else {
                    modalBody.innerHTML = '<div class="alert alert-success"><i class="fas fa-info-circle me-2"></i>This file type cannot be previewed. <a href="' + file + '" target="_blank" class="btn btn-success ms-2"><i class="fas fa-external-link-alt me-1"></i>Open in New Tab</a></div>';
                }
            }, 500);
        });
    }
});

// Grade input validation for 99.99 format
document.addEventListener('DOMContentLoaded', function() {
    const gradeInputs = document.querySelectorAll('.grade-input');
    
    gradeInputs.forEach(input => {
        // Prevent non-numeric characters except decimal point
        input.addEventListener('keypress', function(e) {
            const char = String.fromCharCode(e.which);
            const currentValue = this.value;
            
            // Allow: backspace, delete, tab, escape, enter
            if ([8, 9, 27, 13, 46].indexOf(e.keyCode) !== -1 ||
                // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                (e.keyCode === 65 && e.ctrlKey === true) ||
                (e.keyCode === 67 && e.ctrlKey === true) ||
                (e.keyCode === 86 && e.ctrlKey === true) ||
                (e.keyCode === 88 && e.ctrlKey === true)) {
                return;
            }
            
            // Allow only numbers and one decimal point
            if (!/[0-9.]/.test(char)) {
                e.preventDefault();
                return;
            }
            
            // Allow only one decimal point
            if (char === '.' && currentValue.indexOf('.') !== -1) {
                e.preventDefault();
                return;
            }
        });
        
        // Minimal validation on input - only block invalid characters
        input.addEventListener('input', function(e) {
            let value = this.value;
            
            // Only remove non-numeric characters except decimal point
            const cleanedValue = value.replace(/[^0-9.]/g, '');
            
            // Only update if there was actually an invalid character
            if (cleanedValue !== value) {
                this.value = cleanedValue;
            }
        });
        
        // Validate on blur (final validation only)
        input.addEventListener('blur', function() {
            let value = this.value.trim();
            
            // Clear any previous validation
            this.classList.remove('is-invalid');
            this.setCustomValidity('');
            
            // Only validate if there's actually a value
            if (value === '') {
                return;
            }
            
            const numValue = parseFloat(value);
            
            // Only show error for truly invalid values
            if (isNaN(numValue) || numValue < 0 || numValue > 100) {
                this.classList.add('is-invalid');
                this.setCustomValidity('Please enter a valid grade between 0 and 100');
            }
        });
    });
});
</script>
